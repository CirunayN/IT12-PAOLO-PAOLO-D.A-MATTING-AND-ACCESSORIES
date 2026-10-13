<?php

namespace App\Http\Controllers;

use App\Models\AccountRecoveryCode;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function index(): Response
    {
        $users = User::query()->whereNotIn('role', ['Admin', 'Owner'])->orderBy('name')->get();

        return response()->view('security.index', compact('users'))->header('Cache-Control', 'no-store, private');
    }

    /**
     * Create an employee directly after the currently logged-in administrator
     * confirms the action with their own password. No email/Gmail verification
     * or pending registration code is used.
     */
    public function storeUser(Request $request, AccountRecoveryService $recovery): RedirectResponse
    {
        $validated = $request->validateWithBag(
            'createEmployee',
            [
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
                'admin_password' => [
                    'required',
                    'current_password',
                ],
            ],
            [
                'name.required' => 'Enter the employee name.',
                'username.required' => 'Enter a username for the employee.',
                'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
                'username.unique' => 'That username is already being used.',
                'email.required' => 'Enter an email address for the employee record.',
                'email.email' => 'Enter a valid email address.',
                'email.unique' => 'That email address is already being used by another account.',
                'password.required' => 'Create an initial password for the employee.',
                'password.min' => 'The employee password must contain at least 8 characters.',
                'password.max' => 'The employee password must not exceed 16 characters.',
                'password.regex' => 'The employee password may only contain uppercase letters, lowercase letters, and numbers.',
                'password.confirmed' => 'The employee password confirmation does not match.',
                'admin_password.required' => 'Enter your administrator password to confirm employee creation.',
                'admin_password.current_password' => 'The administrator password is incorrect.',
            ]
        );

        [$employee, $recoveryCode] = DB::transaction(function () use ($validated, $recovery) {
            $employee = User::create([
                'name' => trim($validated['name']),
                'username' => trim($validated['username']),
                'email' => strtolower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'role' => 'Employee',
                'is_active' => true,
                // Kept populated for compatibility with the existing login
                // gate. This is NOT the result of email/Gmail verification.
                'email_verified_at' => now(),
            ]);

            return [$employee, $recovery->generate($employee)];
        });

        return redirect()
            ->route('security.index')
            ->with('created_employee_recovery_code', $recovery->encryptedDisplay(['code' => $recoveryCode, 'username' => $employee->username]))
            ->with(
                'success',
                "Employee account for {$employee->name} was created successfully. No email verification is required."
            );
    }

    public function toggleUser(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Administrator accounts cannot be disabled from employee access control.');
        }

        $newState = ! $user->is_active;

        DB::transaction(function () use ($user, $newState) {
            $user->forceFill([
                'is_active' => $newState,
                'remember_token' => $newState ? $user->remember_token : null,
            ])->save();

            if (! $newState) {
                AccountRecoveryCode::where('user_id', $user->id)->update(['verified_token_hash' => null, 'verified_until' => null]);

                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();
            }
        });

        return back()->with(
            'success',
            $newState
                ? "{$user->name} can access the system again."
                : "{$user->name} has been disabled and can no longer access the system."
        );
    }
}
