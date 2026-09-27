<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the currently logged-in user's password.
     */
    public function update(Request $request): RedirectResponse
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
                'current_password.required' =>
                    'Please enter your current password.',

                'current_password.current_password' =>
                    'The current password you entered is incorrect.',

                'password.required' =>
                    'Please enter your new password.',

                'password.min' =>
                    'The new password must contain at least 8 characters.',

                'password.max' =>
                    'The new password must not exceed 16 characters.',

                'password.regex' =>
                    'The password may only contain uppercase letters, lowercase letters, and numbers.',

                'password.confirmed' =>
                    'The new password and confirmation do not match.',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent using the same password
        |--------------------------------------------------------------------------
        */

        if (
            Hash::check(
                $validated['password'],
                $request->user()->password
            )
        ) {

            return back()
                ->withErrors(
                    [
                        'password' =>
                            'Your new password must be different from your current password.',
                    ],
                    'updatePassword'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Change Password
        |--------------------------------------------------------------------------
        */

        $request->user()->update([
            'password' =>
                Hash::make(
                    $validated['password']
                ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Log User Out
        |--------------------------------------------------------------------------
        |
        | After changing the password, invalidate the current session.
        |
        */

        Auth::guard('web')->logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();


        /*
        |--------------------------------------------------------------------------
        | Return to Login
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Password changed successfully. Please sign in again using your new password.'
            );
    }
}
