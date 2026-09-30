<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt login, then refuse the session when the account
     * has not completed verification or has been disabled.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(
            $this->only('username', 'password'),
            $this->boolean('remember')
        )) {
            $throttleKey = $this->throttleKey();
            $attemptsKey = 'login_attempts:' . $throttleKey;
            $tierKey = 'login_tier:' . $throttleKey;
            $lockoutUntilKey = 'login_lockout_until:' . $throttleKey;

            $attempts = (int) Cache::get($attemptsKey, 0) + 1;

            Cache::put($attemptsKey, $attempts, now()->addDays(2));

            if ($attempts % 3 === 0) {
                $currentTier = (int) Cache::get($tierKey, 0) + 1;
                Cache::put($tierKey, $currentTier, now()->addDays(2));

                $lockoutMinutes = $this->getLockoutMinutes($currentTier);
                $lockoutUntil = now()->addMinutes($lockoutMinutes)->timestamp;

                Cache::put(
                    $lockoutUntilKey,
                    $lockoutUntil,
                    now()->addMinutes($lockoutMinutes)
                );

                event(new Lockout($this));

                throw ValidationException::withMessages([
                    'username' =>
                        "Failed 3 login attempts. Your account is blocked for " .
                        "{$lockoutMinutes} minute(s). Please try again after the lockout expires.",
                ]);
            }

            $remaining = 3 - ($attempts % 3);

            throw ValidationException::withMessages([
                'username' =>
                    "Invalid username or password. You have {$remaining} " .
                    "attempt(s) remaining before your account is blocked.",
            ]);
        }

        $user = Auth::user();

        if (!$user || !$user->is_active) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'username' =>
                    'This account has been disabled by the administrator. ' .
                    'Access is no longer allowed.',
            ]);
        }

        if (is_null($user->email_verified_at)) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'username' =>
                    'This account has not been verified yet. ' .
                    'Ask the administrator to complete employee verification before signing in.',
            ]);
        }

        $this->clearRateLimiting();
    }

    public function ensureIsNotRateLimited(): void
    {
        $lockoutUntilKey = 'login_lockout_until:' . $this->throttleKey();
        $lockoutUntil = Cache::get($lockoutUntilKey);

        if ($lockoutUntil && now()->timestamp < $lockoutUntil) {
            event(new Lockout($this));

            $seconds = $lockoutUntil - now()->timestamp;
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'username' =>
                    "Account is temporarily blocked due to repeated failed attempts. " .
                    "Please try again in {$minutes} minute(s) ({$seconds} seconds remaining).",
            ]);
        }
    }

    public function clearRateLimiting(): void
    {
        $throttleKey = $this->throttleKey();

        Cache::forget('login_attempts:' . $throttleKey);
        Cache::forget('login_tier:' . $throttleKey);
        Cache::forget('login_lockout_until:' . $throttleKey);
    }

    protected function getLockoutMinutes(int $tier): int
    {
        $tiers = [
            1 => 1,
            2 => 5,
            3 => 10,
            4 => 20,
        ];

        if (isset($tiers[$tier])) {
            return $tiers[$tier];
        }

        $minutes = 20 * pow(2, $tier - 4);

        return (int) min(1440, $minutes);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('username')) . '|' . $this->ip()
        );
    }
}
