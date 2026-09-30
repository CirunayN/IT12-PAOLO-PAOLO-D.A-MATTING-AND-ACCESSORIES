<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_sale', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_sale', 'GCash_Reference_Number')) {
                $table
                    ->string('GCash_Reference_Number', 100)
                    ->nullable()
                    ->after('Change_Amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_sale', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_sale', 'GCash_Reference_Number')) {
                $table->dropColumn('GCash_Reference_Number');
            }
        });
    }
};
