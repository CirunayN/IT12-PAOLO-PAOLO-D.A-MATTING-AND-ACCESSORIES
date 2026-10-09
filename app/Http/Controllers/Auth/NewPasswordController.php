<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\SecurityCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('password_reset_verified_user_id');

        if (!$userId) {
            return redirect()->route('password.request');
        }

        $user = User::find($userId);

        if (!$user || !$user->is_active) {
            return redirect()->route('password.request')->withErrors([
                'username' => 'That account is not available for password recovery.',
            ]);
        }

        return view('auth.reset-password', compact('user'));
    }

    public function store(
        Request $request,
        SecurityCodeService $codes
    ): RedirectResponse {
        $userId = $request->session()->get('password_reset_verified_user_id');

        if (!$userId) {
            return redirect()->route('password.request');
        }

        $user = User::findOrFail($userId);

        if (!$user->is_active) {
            return redirect()->route('password.request')->withErrors([
                'username' => 'That account has been disabled.',
            ]);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'max:16',
                'regex:/^[A-Za-z0-9]+$/',
                'confirmed',
            ],
        ], [
            'password.min' => 'The password must contain at least 8 characters.',
            'password.max' => 'The password must not exceed 16 characters.',
            'password.regex' => 'The password may only contain uppercase letters, lowercase letters, and numbers.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Your new password must be different from your current password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $resetRequestId = $request->session()->get('password_reset_request_id');

        if ($resetRequestId) {
            PasswordResetRequest::whereKey($resetRequestId)
                ->where('user_id', $user->id)
                ->update(['completed_at' => now()]);
        }

        $newRecoveryCodes = $user->isAdmin()
            ? $codes->rotateAdminRecoveryCodes($user)
            : null;

        $request->session()->forget([
            'password_reset_mode',
            'password_reset_user_id',
            'password_reset_request_id',
            'password_reset_verified_user_id',
        ]);

        $request->session()->regenerateToken();

        if ($newRecoveryCodes) {
            $request->session()->put('new_admin_recovery_codes', $newRecoveryCodes);

            return redirect()->route('password.recovery-codes');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Password reset successfully. You can now sign in using your new password.');
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->pull('new_admin_recovery_codes');

        if (!$codes || !is_array($codes)) {
            return redirect()->route('login');
        }

        $request->session()->put('recovery_codes_export_until', time() + 600);
        return view('auth.admin-recovery-codes', compact('codes'));
    }
}
