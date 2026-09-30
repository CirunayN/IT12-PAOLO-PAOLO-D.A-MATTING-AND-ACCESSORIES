<?php

namespace App\Services;

use App\Models\AdminRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecurityCodeService
{
    private const ALPHANUMERIC = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generateFormattedCode(): string
    {
        $raw = '';
        $max = strlen(self::ALPHANUMERIC) - 1;

        for ($i = 0; $i < 16; $i++) {
            $raw .= self::ALPHANUMERIC[random_int(0, $max)];
        }

        return implode('-', str_split($raw, 4));
    }

    /**
     * Replace every existing recovery code for an admin with a new set of 10.
     * Plaintext codes are returned only to the caller for immediate display.
     * Only hashes are stored in the database.
     *
     * @return array<int, string>
     */
    public function rotateAdminRecoveryCodes(User $admin): array
    {
        if (!$admin->isAdmin()) {
            throw new \InvalidArgumentException('Recovery codes can only be generated for an administrator.');
        }

        return DB::transaction(function () use ($admin) {
            AdminRecoveryCode::where('user_id', $admin->id)->delete();

            $codes = [];

            while (count($codes) < 10) {
                $code = $this->generateFormattedCode();

                if (in_array($code, $codes, true)) {
                    continue;
                }

                $codes[] = $code;

                AdminRecoveryCode::create([
                    'user_id' => $admin->id,
                    'code_hash' => Hash::make($code),
                ]);
            }

            return $codes;
        });
    }
}
