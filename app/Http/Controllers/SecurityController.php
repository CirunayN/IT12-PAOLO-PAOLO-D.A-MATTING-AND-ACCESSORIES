<?php

namespace App\Http\Controllers;

use App\Models\AdminRecoveryCode;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\SecurityCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        PasswordResetRequest::query()
            ->whereNull('used_at')
            ->whereNull('completed_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'code_hash' => null,
                'approved_by' => null,
                'approved_at' => null,
                'expires_at' => null,
            ]);

        $resetRequests = PasswordResetRequest::with('user')
            ->whereNull('used_at')
            ->whereNull('completed_at')
            ->latest('created_at')
            ->get();

        $users = User::query()
            ->whereNotIn('role', ['Admin', 'Owner'])
            ->orderBy('name')
            ->get();

        $unusedRecoveryCodes = AdminRecoveryCode::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('used_at')
            ->count();

        return view('security.index', compact(
            'resetRequests',
            'users',
            'unusedRecoveryCodes'
        ));
    }

    /**
     * Create an employee directly after the currently logged-in administrator
     * confirms the action with their own password. No email/Gmail verification
     * or pending registration code is used.
     */
    public function storeUser(Request $request): RedirectResponse
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

        $employee = DB::transaction(function () use ($validated) {
            return User::create([
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
        });

        return redirect()
            ->route('security.index')
            ->with(
                'success',
                "Employee account for {$employee->name} was created successfully. No email verification is required."
            );
    }

    public function approveReset(
        PasswordResetRequest $passwordResetRequest,
        SecurityCodeService $codes
    ): RedirectResponse {
        $passwordResetRequest->load('user');

        if (!$passwordResetRequest->user || $passwordResetRequest->user->isAdmin()) {
            abort(422, 'Administrator accounts do not use employee approval codes.');
        }

        if (!$passwordResetRequest->user->is_active) {
            return back()->with('error', 'The account is disabled. Enable it before approving a password reset.');
        }

        if ($passwordResetRequest->used_at || $passwordResetRequest->completed_at) {
            return back()->with('error', 'This reset request has already been used.');
        }

        $code = $codes->generateFormattedCode();

        $passwordResetRequest->update([
            'code_hash' => Hash::make($code),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'expires_at' => now()->addDay(),
            'used_at' => null,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('security.index')
            ->with('approved_reset_code', $code)
            ->with('approved_reset_user', $passwordResetRequest->user->name)
            ->with('approved_reset_expiry', now()->addDay()->format('M d, Y h:i A'));
    }

    public function denyReset(PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        $passwordResetRequest->delete();

        return redirect()
            ->route('security.index')
            ->with('success', 'Password reset request denied and removed.');
    }

    public function toggleUser(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Administrator accounts cannot be disabled from employee access control.');
        }

        $newState = !$user->is_active;

        DB::transaction(function () use ($user, $newState) {
            $user->forceFill([
                'is_active' => $newState,
                'remember_token' => $newState ? $user->remember_token : null,
            ])->save();

            if (!$newState) {
                PasswordResetRequest::where('user_id', $user->id)->delete();

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

    public function regenerateRecoveryCodes(
        Request $request,
        SecurityCodeService $codes
    ): RedirectResponse {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required' => 'Enter your current password before generating recovery codes.',
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        $generated = $codes->rotateAdminRecoveryCodes($request->user());

        return redirect()
            ->route('security.index')
            ->with('generated_recovery_codes', $generated)
            ->with('success', 'A new set of 10 administrator recovery codes was generated. All previous recovery codes are now invalid.');
    }
}
