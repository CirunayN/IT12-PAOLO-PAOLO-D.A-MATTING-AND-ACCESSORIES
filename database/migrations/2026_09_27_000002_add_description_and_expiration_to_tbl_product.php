<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_product', function (Blueprint $table) {
            $table->text('Description')
                ->nullable()
                ->after('Name');

            $table->boolean('Has_Expiration')
                ->default(false)
                ->after('Description');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_product', function (Blueprint $table) {
            $table->dropColumn([
                'Description',
                'Has_Expiration',
            ]);
        });
    }
};
