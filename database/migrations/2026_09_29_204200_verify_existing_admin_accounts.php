<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Existing Admin/Owner accounts predate the employee verification
         * workflow. Mark only those trusted privileged accounts as verified
         * during the upgrade so the administrator is not locked out.
         *
         * Existing employee/cashier accounts with email_verified_at = NULL
         * remain unverified and will be blocked by LoginRequest.
         */
        DB::table('users')
            ->whereIn('role', ['Admin', 'Owner'])
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => now(),
            ]);
    }

    public function down(): void
    {
        /*
         * Intentionally no-op.
         *
         * We cannot safely know which Admin/Owner accounts were already
         * verified before this migration, so rollback must not erase
         * verification timestamps.
         */
    }
};
