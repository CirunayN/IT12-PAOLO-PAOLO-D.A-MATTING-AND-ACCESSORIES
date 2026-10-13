<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RecoveryCodeController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function verify(Request $request, AccountRecoveryService $recovery): RedirectResponse
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => trim($request->input('username'))]);
        }
        if (is_string($request->input('recovery_code'))) {
            $request->merge(['recovery_code' => strtoupper(trim($request->input('recovery_code')))]);
        }
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'recovery_code' => ['required', 'string', 'regex:/^[A-Z0-9]{4}(-[A-Z0-9]{4}){3}$/'],
        ], ['recovery_code.regex' => 'Enter your saved recovery code in XXXX-XXXX-XXXX-XXXX format.']);
        $grant = $recovery->verify($validated['username'], $validated['recovery_code']);
        if (! $grant) {
            return back()->withInput($request->only('username'))->withErrors([
                'recovery_code' => 'The username or recovery code is invalid, or recovery is temporarily locked. After five incorrect attempts, wait 15 minutes before trying again.',
            ]);
        }
        $request->session()->regenerate();
        $request->session()->put('account_password_reset_grant', $grant);

        return redirect()->route('password.reset');
    }

    public function generate(Request $request, AccountRecoveryService $recovery): RedirectResponse
    {
        $request->validateWithBag('generateRecoveryCode', [
            'current_password' => ['required', 'current_password'],
        ], ['current_password.current_password' => 'The current password is incorrect.']);

        return redirect()->route('settings.password')->with('generated_recovery_code', $recovery->encryptedDisplay([
            'code' => $recovery->generate($request->user()), 'username' => $request->user()->username,
        ]));
    }

    public function showReplacement(Request $request, AccountRecoveryService $codes): Response|RedirectResponse
    {
        $recovery = $codes->revealDisplay($request->session()->pull('replacement_recovery_code'));

        return is_array($recovery) && isset($recovery['code'], $recovery['username'])
            ? response()->view('auth.recovery-code', compact('recovery'))
                ->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache')
            : redirect()->route('login');
    }
}
