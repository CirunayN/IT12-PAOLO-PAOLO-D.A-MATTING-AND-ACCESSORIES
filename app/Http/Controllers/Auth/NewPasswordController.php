<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', compact('email'));
    }

    public function store(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified_email');

        if (!$email) {
            return redirect()->route('password.request');
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

        $user = User::where('email', $email)->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        PasswordResetCode::where('email', $email)->delete();

        $request->session()->forget([
            'password_reset_email',
            'password_reset_verified_email',
        ]);

        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Password reset successfully. You can now sign in using your new password.');
    }
}
