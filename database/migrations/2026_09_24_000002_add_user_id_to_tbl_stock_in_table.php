<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_Stock_in') && !Schema::hasColumn('tbl_Stock_in', 'User_ID')) {
            Schema::table('tbl_Stock_in', function (Blueprint $table) {
                $table->unsignedBigInteger('User_ID')->nullable()->after('Product_ID');
                $table->foreign('User_ID')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_Stock_in') && Schema::hasColumn('tbl_Stock_in', 'User_ID')) {
            Schema::table('tbl_Stock_in', function (Blueprint $table) {
                $table->dropForeign(['User_ID']);
                $table->dropColumn('User_ID');
            });
        }
    }
};
