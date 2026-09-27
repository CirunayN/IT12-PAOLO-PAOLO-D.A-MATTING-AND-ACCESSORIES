<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'tbl_sale',
            function (Blueprint $table) {
                $table
                    ->decimal(
                        'Amount_Received',
                        10,
                        2
                    )
                    ->default(0)
                    ->after('Total');

                $table
                    ->decimal(
                        'Change_Amount',
                        10,
                        2
                    )
                    ->default(0)
                    ->after('Amount_Received');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'tbl_sale',
            function (Blueprint $table) {
                $table->dropColumn([
                    'Amount_Received',
                    'Change_Amount',
                ]);
            }
        );
    }
};
