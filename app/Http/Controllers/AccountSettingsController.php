<?php

namespace App\Http\Controllers;

use App\Mail\EmailChangeCodeMail;
use App\Models\EmailChangeCode;
use App\Models\PasswordResetCode;
use App\Models\PendingEmployeeRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AccountSettingsController extends Controller
{
    public function show(Request $request): View
    {
        $pending = EmailChangeCode::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if ($pending && $pending->expires_at->isPast()) {
            $pending->delete();
            $pending = null;
        }

        return view('settings.account', [
            'user' => $request->user(),
            'pending' => $pending,
        ]);
    }

    public function sendEmailChangeCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'current_password.current_password' => 'The current password you entered is incorrect.',
            'email.required' => 'Please enter your new email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'That email address is already being used by another system account.',
        ]);

        $newEmail = strtolower(trim($validated['email']));
        $currentEmail = strtolower(trim((string) $user->email));

        if ($newEmail === $currentEmail) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'The new email address must be different from your current email address.',
                ]);
        }

        if (
            PendingEmployeeRegistration::where('email', $newEmail)
                ->where('expires_at', '>', now())
                ->exists()
        ) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'That email address is currently being used for a pending employee registration.',
                ]);
        }

        $code = (string) random_int(100000, 999999);

        EmailChangeCode::where('user_id', $user->id)->delete();

        $pending = EmailChangeCode::create([
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($newEmail)->send(
                new EmailChangeCodeMail(
                    code: $code,
                    accountName: $user->name,
                    newEmail: $newEmail,
                )
            );
        } catch (Throwable $e) {
            $pending->delete();

            Log::error('Account email-change verification email failed.', [
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Unable to send the Gmail verification code. Check the internet connection and mail settings, then try again.',
                ]);
        }

        return redirect()
            ->route('settings.email.verify.form')
            ->with('status', 'A 6-digit verification code was sent to your new email address. Your current email will remain unchanged until the code is verified.');
    }

    public function verifyEmailForm(Request $request): View|RedirectResponse
    {
        $pending = $this->getPending($request);

        if (!$pending) {
            return redirect()
                ->route('settings.account')
                ->with('error', 'There is no active email-change request.');
        }

        return view('settings.verify-email-change', [
            'user' => $request->user(),
            'pending' => $pending,
        ]);
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $pending = $this->getPending($request);

        if (!$pending) {
            return redirect()
                ->route('settings.account')
                ->with('error', 'There is no active email-change request.');
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        if ($pending->expires_at->isPast()) {
            $pending->delete();

            return redirect()
                ->route('settings.account')
                ->with('error', 'The email verification code expired. Start the email change again.');
        }

        if (!Hash::check($validated['code'], $pending->code_hash)) {
            return back()->withErrors([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        $user = $request->user();
        $oldEmail = $user->email;
        $newEmail = strtolower(trim($pending->new_email));

        if (
            User::where('email', $newEmail)
                ->where('id', '!=', $user->id)
                ->exists()
        ) {
            $pending->delete();

            return redirect()
                ->route('settings.account')
                ->withErrors([
                    'email' => 'That email address is already being used by another system account.',
                ]);
        }

        DB::transaction(function () use ($user, $pending, $oldEmail, $newEmail) {
            $user->forceFill([
                'email' => $newEmail,
                'email_verified_at' => now(),
            ])->save();

            PasswordResetCode::whereIn('email', [
                strtolower(trim((string) $oldEmail)),
                $newEmail,
            ])->delete();

            $pending->delete();
        });

        return redirect()
            ->route('settings.account')
            ->with('success', 'Your email address was changed and verified successfully.');
    }

    public function resendEmailChangeCode(Request $request): RedirectResponse
    {
        $pending = $this->getPending($request, allowExpired: true);

        if (!$pending) {
            return redirect()
                ->route('settings.account')
                ->with('error', 'There is no email-change request to resend.');
        }

        $code = (string) random_int(100000, 999999);

        try {
            Mail::to($pending->new_email)->send(
                new EmailChangeCodeMail(
                    code: $code,
                    accountName: $request->user()->name,
                    newEmail: $pending->new_email,
                )
            );
        } catch (Throwable $e) {
            Log::error('Account email-change code resend failed.', [
                'user_id' => $request->user()->id,
                'new_email' => $pending->new_email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'code' => 'Unable to resend the Gmail verification code. Check the internet connection and mail settings.',
            ]);
        }

        $pending->update([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        return redirect()
            ->route('settings.email.verify.form')
            ->with('status', 'A new 6-digit verification code was sent. The previous code is no longer valid.');
    }

    public function cancelEmailChange(Request $request): RedirectResponse
    {
        EmailChangeCode::where('user_id', $request->user()->id)->delete();

        return redirect()
            ->route('settings.account')
            ->with('success', 'The pending email change was cancelled.');
    }

    private function getPending(Request $request, bool $allowExpired = false): ?EmailChangeCode
    {
        $pending = EmailChangeCode::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if (!$pending) {
            return null;
        }

        if (!$allowExpired && $pending->expires_at->isPast()) {
            $pending->delete();
            return null;
        }

        return $pending;
    }
}
