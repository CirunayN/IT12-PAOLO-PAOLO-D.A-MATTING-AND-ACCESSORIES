<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | STOCK-IN
        |--------------------------------------------------------------------------
        | Expiration belongs to each received batch.
        */

        Schema::table('tbl_stock_in', function (Blueprint $table) {

            if (!Schema::hasColumn('tbl_stock_in', 'Has_Expiration')) {
                $table->boolean('Has_Expiration')
                    ->default(false)
                    ->after('Retail_Price');
            }

            if (!Schema::hasColumn('tbl_stock_in', 'Expiration_Date')) {
                $table->date('Expiration_Date')
                    ->nullable()
                    ->after('Has_Expiration');
            }

            if (!Schema::hasColumn('tbl_stock_in', 'Condition')) {
                $table->string('Condition', 50)
                    ->default('Good')
                    ->after('Expiration_Date');
            }

        });


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        | Product description stays.
        | Expiration does NOT belong to the product itself.
        */

        Schema::table('tbl_product', function (Blueprint $table) {

            if (Schema::hasColumn('tbl_product', 'Expiration_Date')) {
                $table->dropColumn('Expiration_Date');
            }

            if (Schema::hasColumn('tbl_product', 'Has_Expiration')) {
                $table->dropColumn('Has_Expiration');
            }

            if (Schema::hasColumn('tbl_product', 'Condition')) {
                $table->dropColumn('Condition');
            }

        });
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Restore product expiration flag if rolled back
        |--------------------------------------------------------------------------
        */

        Schema::table('tbl_product', function (Blueprint $table) {

            if (!Schema::hasColumn('tbl_product', 'Has_Expiration')) {
                $table->boolean('Has_Expiration')
                    ->default(false)
                    ->after('Description');
            }

        });


        /*
        |--------------------------------------------------------------------------
        | Remove Stock-In fields
        |--------------------------------------------------------------------------
        */

        Schema::table('tbl_stock_in', function (Blueprint $table) {

            $columns = [];

            if (Schema::hasColumn('tbl_stock_in', 'Has_Expiration')) {
                $columns[] = 'Has_Expiration';
            }

            if (Schema::hasColumn('tbl_stock_in', 'Expiration_Date')) {
                $columns[] = 'Expiration_Date';
            }

            if (Schema::hasColumn('tbl_stock_in', 'Condition')) {
                $columns[] = 'Condition';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }

        });
    }
};
