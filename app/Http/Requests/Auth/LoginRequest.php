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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('username', 'password'), $this->boolean('remember'))) {
            $throttleKey = $this->throttleKey();
            $attemptsKey = 'login_attempts:' . $throttleKey;
            $tierKey = 'login_tier:' . $throttleKey;
            $lockoutUntilKey = 'login_lockout_until:' . $throttleKey;

            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addDays(2));

            // Escalating lockout triggers on every 3 failed attempts
            if ($attempts % 3 === 0) {
                $currentTier = (int) Cache::get($tierKey, 0) + 1;
                Cache::put($tierKey, $currentTier, now()->addDays(2));

                $lockoutMinutes = $this->getLockoutMinutes($currentTier);
                $lockoutUntil = now()->addMinutes($lockoutMinutes)->timestamp;
                Cache::put($lockoutUntilKey, $lockoutUntil, now()->addMinutes($lockoutMinutes));

                event(new Lockout($this));

                throw ValidationException::withMessages([
                    'username' => "Failed 3 login attempts. Your account is blocked for {$lockoutMinutes} minute(s). Please try again after the lockout expires.",
                ]);
            }

            $remaining = 3 - ($attempts % 3);
            throw ValidationException::withMessages([
                'username' => "Invalid username or password. You have {$remaining} attempt(s) remaining before your account is blocked.",
            ]);
        }

        // Successfully logged in: clear lockout and attempt counters
        $this->clearRateLimiting();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $lockoutUntilKey = 'login_lockout_until:' . $this->throttleKey();
        $lockoutUntil = Cache::get($lockoutUntilKey);

        if ($lockoutUntil && now()->timestamp < $lockoutUntil) {
            event(new Lockout($this));

            $seconds = $lockoutUntil - now()->timestamp;
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'username' => "Account is temporarily blocked due to repeated failed attempts. Please try again in {$minutes} minute(s) ({$seconds} seconds remaining).",
            ]);
        }
    }

    /**
     * Clear all rate limiting and tier tracking for this user.
     */
    public function clearRateLimiting(): void
    {
        $throttleKey = $this->throttleKey();
        Cache::forget('login_attempts:' . $throttleKey);
        Cache::forget('login_tier:' . $throttleKey);
        Cache::forget('login_lockout_until:' . $throttleKey);
    }

    /**
     * Calculate escalating lockout minutes based on tier.
     * Sequence: 1m, 5m, 10m, 20m, 40m, 80m... capped at 1 day (1440m).
     */
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

        // For tier 5+: double successively, capped at 1440 minutes (24 hours / 1 day)
        $minutes = 20 * pow(2, $tier - 4);
        return (int) min(1440, $minutes);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
