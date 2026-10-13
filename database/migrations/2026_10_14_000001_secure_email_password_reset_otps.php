<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('password_reset_codes', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0);
            }
            if (! Schema::hasColumn('password_reset_codes', 'verified_token_hash')) {
                $table->string('verified_token_hash', 64)->nullable();
            }
            if (! Schema::hasColumn('password_reset_codes', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (! Schema::hasColumn('password_reset_codes', 'used_at')) {
                $table->timestamp('used_at')->nullable();
            }
        });
        // Codes issued by older versions must not authorize the new flow.
        DB::table('password_reset_codes')->delete();
    }

    public function down(): void
    {
        Schema::table('password_reset_codes', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'verified_token_hash', 'verified_at', 'used_at']);
        });
    }
};
