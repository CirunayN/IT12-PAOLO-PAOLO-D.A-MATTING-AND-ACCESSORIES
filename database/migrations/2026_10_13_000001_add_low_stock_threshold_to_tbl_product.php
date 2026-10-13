<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tbl_product', 'Low_Stock_Threshold')) return;
        Schema::table('tbl_product', function (Blueprint $table) {
            $table->unsignedInteger('Low_Stock_Threshold')->default(5);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_product', function (Blueprint $table) {
            $table->dropColumn('Low_Stock_Threshold');
        });
    }
};
