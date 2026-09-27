<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'exists:users,email',
            ],
        ], [
            'email.exists' => 'No system account is registered with that email address.',
        ]);

        $email = strtolower(trim($validated['email']));
        $code = (string) random_int(100000, 999999);

        PasswordResetCode::where('email', $email)->delete();

        $record = PasswordResetCode::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($email)->send(
                new PasswordResetCodeMail($code)
            );
        } catch (Throwable $e) {
            $record->delete();

            Log::error('Password reset code email failed.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Unable to send the Gmail verification code. Check the internet connection and mail settings, then try again.',
                ]);
        }

        $request->session()->put('password_reset_email', $email);
        $request->session()->forget('password_reset_verified_email');

        return redirect()
            ->route('password.code.form')
            ->with('status', 'A 6-digit verification code was sent to your email. The code expires in 10 minutes.');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-password-code', compact('email'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        $record = PasswordResetCode::where('email', $email)
            ->latest('id')
            ->first();

        if (!$record) {
            return back()->withErrors([
                'code' => 'No active verification code was found. Request a new code.',
            ]);
        }

        if ($record->expires_at->isPast()) {
            $record->delete();

            return back()->withErrors([
                'code' => 'This verification code has expired. Request a new code.',
            ]);
        }

        if (!Hash::check($validated['code'], $record->code_hash)) {
            return back()->withErrors([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        $record->delete();

        $request->session()->put('password_reset_verified_email', $email);

        return redirect()->route('password.reset');
    }
}
