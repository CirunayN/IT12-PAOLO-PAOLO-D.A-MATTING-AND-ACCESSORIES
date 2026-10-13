<?php

namespace App\Services;

use App\Models\AccountRecoveryCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountRecoveryService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_MINUTES = 15;

    public const RESET_MINUTES = 10;

    public function encryptedDisplay(array $recovery): array
    {
        return ['code' => Crypt::encryptString($recovery['code']), 'username' => $recovery['username']];
    }

    public function revealDisplay(?array $recovery): ?array
    {
        if (! $recovery || ! is_string($recovery['code'] ?? null) || ! is_string($recovery['username'] ?? null)) {
            return null;
        }
        try {
            $recovery['code'] = Crypt::decryptString($recovery['code']);
        } catch (DecryptException) {
            return null;
        }

        return $recovery;
    }

    public function generate(User $user): string
    {
        return DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $code = '';
            for ($index = 0; $index < 16; $index++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = implode('-', str_split($code, 4));
            AccountRecoveryCode::where('user_id', $user->id)->delete();
            AccountRecoveryCode::create(['user_id' => $user->id, 'code_hash' => Hash::make($code), 'attempts' => 0]);

            return $code;
        });
    }

    public function verify(string $username, string $code): ?array
    {
        return DB::transaction(function () use ($username, $code) {
            $user = User::where('username', $username)->where('is_active', true)->lockForUpdate()->first();
            if (! $user) {
                return null;
            }
            $record = AccountRecoveryCode::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $record || ($record->locked_until && $record->locked_until->gt(now()))) {
                return null;
            }
            if ($record->locked_until) {
                $record->update(['attempts' => 0, 'locked_until' => null]);
            }
            if (! Hash::check($code, $record->code_hash)) {
                $attempts = $record->attempts + 1;
                $record->update([
                    'attempts' => $attempts,
                    'locked_until' => $attempts >= self::MAX_ATTEMPTS ? now()->addMinutes(self::LOCK_MINUTES) : null,
                ]);

                return null;
            }

            $token = Str::random(64);
            $record->update([
                'attempts' => 0, 'verified_token_hash' => hash('sha256', $token),
                'verified_until' => now()->addMinutes(self::RESET_MINUTES),
            ]);

            return ['id' => $record->id, 'user_id' => $user->id, 'token' => $token];
        });
    }

    public function validGrant(?array $grant, bool $lock = false): ?AccountRecoveryCode
    {
        if (! $grant || ! is_string($grant['token'] ?? null)) {
            return null;
        }
        $query = AccountRecoveryCode::whereKey($grant['id'] ?? null)->where('user_id', $grant['user_id'] ?? null);
        $record = ($lock ? $query->lockForUpdate() : $query)->first();

        return $record && $record->verified_until && $record->verified_until->gt(now())
            && hash_equals((string) $record->verified_token_hash, hash('sha256', $grant['token']))
                ? $record : null;
    }

    public function resetPassword(?array $grant, string $password): array
    {
        $resetUser = null;
        $result = DB::transaction(function () use ($grant, $password, &$resetUser) {
            $user = User::whereKey($grant['user_id'] ?? null)->where('is_active', true)->lockForUpdate()->first();
            $record = $this->validGrant($grant, true);
            if (! $user || ! $record) {
                return ['status' => 'invalid'];
            }
            if (Hash::check($password, $user->password)) {
                return ['status' => 'same-password'];
            }
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $replacement = $this->generate($user);
            $resetUser = $user;

            return ['status' => 'success', 'code' => $replacement, 'username' => $user->username];
        }, 3);
        if ($resetUser) {
            event(new PasswordReset($resetUser));
        }

        return $result;
    }
}
