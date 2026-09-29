<?php

namespace App\Http\Controllers;

use App\Mail\EmployeeRegistrationCodeMail;
use App\Models\PendingEmployeeRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = User::query()
            ->whereNotIn('role', ['Admin', 'Owner'])
            ->orderBy('name')
            ->get();

        $pendingEmployees = PendingEmployeeRegistration::query()
            ->where('created_by', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('employees.index', compact('employees', 'pendingEmployees'));
    }

    public function create(): View
    {
        return view('employees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:users,username',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:16',
                'regex:/^[A-Za-z0-9]+$/',
                'confirmed',
            ],
        ], [
            'username.alpha_dash' =>
                'The username may only contain letters, numbers, dashes, and underscores.',
            'password.min' =>
                'The password must contain at least 8 characters.',
            'password.max' =>
                'The password must not exceed 16 characters.',
            'password.regex' =>
                'The password may only contain uppercase letters, lowercase letters, and numbers.',
            'password.confirmed' =>
                'The password confirmation does not match.',
        ]);

        $email = strtolower(trim($validated['email']));
        $username = trim($validated['username']);
        $code = (string) random_int(100000, 999999);

        PendingEmployeeRegistration::where(function ($query) use ($email, $username) {
            $query->where('email', $email)
                ->orWhere('username', $username);
        })->delete();

        $pending = PendingEmployeeRegistration::create([
            'name' => trim($validated['name']),
            'username' => $username,
            'email' => $email,
            'password_hash' => Hash::make($validated['password']),
            'role' => 'Employee',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'created_by' => auth()->id(),
        ]);

        try {
            Mail::to($email)->send(
                new EmployeeRegistrationCodeMail(
                    $code,
                    $pending->name
                )
            );
        } catch (Throwable $e) {
            $pending->delete();

            Log::error('Employee registration code email failed.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput(
                    $request->except([
                        'password',
                        'password_confirmation',
                    ])
                )
                ->withErrors([
                    'email' =>
                        'Unable to send the Gmail confirmation code. ' .
                        'Check the internet connection and mail settings, then try again.',
                ]);
        }

        $request->session()->put(
            'pending_employee_registration_id',
            $pending->id
        );

        return redirect()
            ->route('employees.verify.form')
            ->with(
                'status',
                'A 6-digit confirmation code was sent. ' .
                'If the employee cannot provide it now, return to Employees and press Verify later.'
            );
    }

    public function resumeVerification(
        Request $request,
        PendingEmployeeRegistration $pending
    ): RedirectResponse {
        if ((int) $pending->created_by !== (int) auth()->id()) {
            abort(
                403,
                'You can only verify employee registrations that you created.'
            );
        }

        $request->session()->put(
            'pending_employee_registration_id',
            $pending->id
        );

        return redirect()->route('employees.verify.form');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $pending = $this->getPendingForCurrentAdmin($request);

        if (!$pending) {
            return redirect()
                ->route('employees.index')
                ->withErrors([
                    'verification' =>
                        'No pending employee verification was selected.',
                ]);
        }

        return view('employees.verify', compact('pending'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $this->getPendingForCurrentAdmin($request);

        if (!$pending) {
            return redirect()
                ->route('employees.index')
                ->withErrors([
                    'verification' =>
                        'No pending employee verification was selected.',
                ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($pending->expires_at->isPast()) {
            return back()->withErrors([
                'code' =>
                    'This confirmation code has expired. ' .
                    'Press Send New Code, then enter the new code.',
            ]);
        }

        if (!Hash::check($validated['code'], $pending->code_hash)) {
            return back()->withErrors([
                'code' => 'The confirmation code is incorrect.',
            ]);
        }

        if (User::where('email', $pending->email)->exists()) {
            return back()->withErrors([
                'code' => 'That email address is already registered.',
            ]);
        }

        if (User::where('username', $pending->username)->exists()) {
            return back()->withErrors([
                'code' => 'That username is already registered.',
            ]);
        }

        DB::transaction(function () use ($pending) {
            User::create([
                'name' => $pending->name,
                'username' => $pending->username,
                'email' => $pending->email,
                'password' => $pending->password_hash,
                'role' => 'Employee',
                'email_verified_at' => now(),
            ]);

            $pending->delete();
        });

        $request->session()->forget(
            'pending_employee_registration_id'
        );

        return redirect()
            ->route('employees.index')
            ->with(
                'success',
                'Employee verified successfully. The account can now log in.'
            );
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $this->getPendingForCurrentAdmin($request);

        if (!$pending) {
            return redirect()
                ->route('employees.index')
                ->withErrors([
                    'verification' =>
                        'No pending employee verification was selected.',
                ]);
        }

        $code = (string) random_int(100000, 999999);

        $pending->update([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($pending->email)->send(
                new EmployeeRegistrationCodeMail(
                    $code,
                    $pending->name
                )
            );
        } catch (Throwable $e) {
            Log::error(
                'Employee registration code resend failed.',
                [
                    'email' => $pending->email,
                    'error' => $e->getMessage(),
                ]
            );

            return back()->withErrors([
                'code' =>
                    'Unable to resend the Gmail confirmation code. ' .
                    'Check the internet connection and mail settings.',
            ]);
        }

        return back()->with(
            'status',
            'A new 6-digit confirmation code was sent. ' .
            'The previous code is no longer valid.'
        );
    }

    private function getPendingForCurrentAdmin(
        Request $request
    ): ?PendingEmployeeRegistration {
        $id = $request->session()->get(
            'pending_employee_registration_id'
        );

        if (!$id) {
            return null;
        }

        return PendingEmployeeRegistration::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();
    }
}
