<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminRecoveryCode;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ], [
            'username.exists' => 'No system account is registered with that username.',
        ]);

        $user = User::where('username', trim($validated['username']))->firstOrFail();

        if (!$user->is_active) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'This account has been disabled by the administrator and cannot request a password reset.',
                ]);
        }

        $request->session()->forget([
            'password_reset_verified_user_id',
            'password_reset_request_id',
            'password_reset_mode',
            'password_reset_user_id',
        ]);

        if ($user->isAdmin()) {
            $request->session()->put([
                'password_reset_mode' => 'admin',
                'password_reset_user_id' => $user->id,
            ]);

            return redirect()
                ->route('password.code.form')
                ->with('status', 'Administrator recovery selected. Enter one unused recovery code from your saved set of 10.');
        }

        $existing = PasswordResetRequest::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        if ($existing && $existing->expires_at && $existing->expires_at->isPast()) {
            $existing->update([
                'code_hash' => null,
                'approved_by' => null,
                'approved_at' => null,
                'expires_at' => null,
            ]);
        }

        if (!$existing) {
            $existing = PasswordResetRequest::create([
                'user_id' => $user->id,
            ]);
        } else {
            $existing->touch();
        }

        $request->session()->put([
            'password_reset_mode' => 'employee',
            'password_reset_user_id' => $user->id,
            'password_reset_request_id' => $existing->id,
        ]);

        $status = $existing->approved_at && $existing->expires_at && $existing->expires_at->isFuture()
            ? 'Your reset request is already approved. Ask the administrator for the active code.'
            : 'Reset request submitted. Ask the administrator to approve it and give you the generated code.';

        return redirect()
            ->route('password.code.form')
            ->with('status', $status);
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $mode = $request->session()->get('password_reset_mode');
        $userId = $request->session()->get('password_reset_user_id');

        if (!$mode || !$userId) {
            return redirect()->route('password.request');
        }

        $user = User::find($userId);

        if (!$user || !$user->is_active) {
            $request->session()->forget([
                'password_reset_mode',
                'password_reset_user_id',
                'password_reset_request_id',
            ]);

            return redirect()->route('password.request')->withErrors([
                'username' => 'That account is not available for password recovery.',
            ]);
        }

        $resetRequest = null;

        if ($mode === 'employee') {
            $resetRequest = PasswordResetRequest::find(
                $request->session()->get('password_reset_request_id')
            );

            if (!$resetRequest || (int) $resetRequest->user_id !== (int) $user->id) {
                return redirect()->route('password.request');
            }
        }

        return view('auth.verify-password-code', compact('mode', 'user', 'resetRequest'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $mode = $request->session()->get('password_reset_mode');
        $userId = $request->session()->get('password_reset_user_id');

        if (!$mode || !$userId) {
            return redirect()->route('password.request');
        }

        $user = User::find($userId);

        if (!$user || !$user->is_active) {
            return redirect()->route('password.request')->withErrors([
                'username' => 'That account is not available for password recovery.',
            ]);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/',
            ],
        ], [
            'code.regex' => 'Enter the code in xxxx-xxxx-xxxx-xxxx format using letters and numbers.',
        ]);

        $code = trim($validated['code']);

        if ($mode === 'admin') {
            $records = AdminRecoveryCode::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->get();

            $matched = $records->first(function (AdminRecoveryCode $record) use ($code) {
                return Hash::check($code, $record->code_hash);
            });

            if (!$matched) {
                return back()->withErrors([
                    'code' => 'That administrator recovery code is invalid or has already been used.',
                ]);
            }

            $matched->update(['used_at' => now()]);
        } else {
            $resetRequest = PasswordResetRequest::find(
                $request->session()->get('password_reset_request_id')
            );

            if (!$resetRequest || (int) $resetRequest->user_id !== (int) $user->id) {
                return redirect()->route('password.request');
            }

            if (!$resetRequest->approved_at || !$resetRequest->code_hash) {
                return back()->withErrors([
                    'code' => 'The administrator has not approved this reset request yet.',
                ]);
            }

            if (!$resetRequest->expires_at || $resetRequest->expires_at->isPast()) {
                $resetRequest->update([
                    'code_hash' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'expires_at' => null,
                ]);

                return back()->withErrors([
                    'code' => 'The approved reset code has expired. Ask the administrator to approve the request again.',
                ]);
            }

            if ($resetRequest->used_at || $resetRequest->completed_at) {
                return back()->withErrors([
                    'code' => 'This reset code has already been used.',
                ]);
            }

            if (!Hash::check($code, $resetRequest->code_hash)) {
                return back()->withErrors([
                    'code' => 'The reset code is incorrect.',
                ]);
            }

            $resetRequest->update(['used_at' => now()]);
        }

        $request->session()->put('password_reset_verified_user_id', $user->id);

        return redirect()->route('password.reset');
    }
}
