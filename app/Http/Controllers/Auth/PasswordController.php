<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    /**
     * Update the currently logged-in user's password.
     */
    public function update(Request $request, AccountRecoveryService $recovery): RedirectResponse
    {
        $validated = $request->validateWithBag(
            'updatePassword',
            [
                'current_password' => [
                    'required',
                    'current_password',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:16',
                    'regex:/^[A-Za-z0-9]+$/',
                    'confirmed',
                ],
            ],
            [
                'current_password.required' => 'Please enter your current password.',
                'current_password.current_password' => 'The current password you entered is incorrect.',
                'password.required' => 'Please enter your new password.',
                'password.min' => 'The new password must contain at least 8 characters.',
                'password.max' => 'The new password must not exceed 16 characters.',
                'password.regex' => 'The password may only contain uppercase letters, lowercase letters, and numbers.',
                'password.confirmed' => 'The new password and confirmation do not match.',
            ]
        );

        $user = $request->user();

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(
                [
                    'password' => 'Your new password must be different from your current password.',
                ],
                'updatePassword'
            );
        }

        $replacement = DB::transaction(function () use ($user, $validated, $recovery) {
            $user->update([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ]);
            DB::table('sessions')->where('user_id', $user->id)->delete();

            return ['code' => $recovery->generate($user), 'username' => $user->username];
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put('replacement_recovery_code', $recovery->encryptedDisplay($replacement));

        return redirect()->route('password.recovery.replacement');
    }
}
