<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_Product') && !Schema::hasColumn('tbl_Product', 'Images')) {
            Schema::table('tbl_Product', function (Blueprint $table) {
                $table->json('Images')->nullable()->after('Image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_Product') && Schema::hasColumn('tbl_Product', 'Images')) {
            Schema::table('tbl_Product', function (Blueprint $table) {
                $table->dropColumn('Images');
            });
        }
    }
};
