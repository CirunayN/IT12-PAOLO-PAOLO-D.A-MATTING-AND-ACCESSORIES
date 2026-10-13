<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, AccountRecoveryService $resets): View|RedirectResponse
    {
        $grant = $request->session()->get('account_password_reset_grant');
        $record = $resets->validGrant($grant);
        $user = $record ? User::whereKey($record->user_id)->where('is_active', true)->first() : null;

        return $user ? view('auth.reset-password', compact('user')) : $this->expired($request);
    }

    public function store(Request $request, AccountRecoveryService $resets): RedirectResponse
    {
        $grant = $request->session()->get('account_password_reset_grant');
        if (! $resets->validGrant($grant)) {
            return $this->expired($request);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:16', 'regex:/^[A-Za-z0-9]+$/', 'confirmed'],
        ], [
            'password.regex' => 'The password may only contain uppercase letters, lowercase letters, and numbers.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $result = $resets->resetPassword($grant, $validated['password']);
        if ($result['status'] === 'same-password') {
            return back()->withErrors(['password' => 'Your new password must be different from your current password.']);
        }
        if ($result['status'] !== 'success') {
            return $this->expired($request);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put('replacement_recovery_code', $resets->encryptedDisplay($result));

        return redirect()->route('password.recovery.replacement');
    }

    private function expired(Request $request): RedirectResponse
    {
        $request->session()->forget('account_password_reset_grant');

        return redirect()->route('password.request')->withErrors([
            'recovery_code' => 'Verify your saved recovery code before resetting your password.',
        ]);
    }
}
