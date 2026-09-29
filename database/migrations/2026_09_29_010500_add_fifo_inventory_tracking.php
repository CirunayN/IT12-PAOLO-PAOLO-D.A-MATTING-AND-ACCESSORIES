<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_stock_in', 'Remaining_Quantity')) {
            Schema::table('tbl_stock_in', function (Blueprint $table) {
                $table->decimal('Remaining_Quantity', 10, 2)
                    ->default(0)
                    ->after('Quantity');
            });
        }

        $productIds = DB::table('tbl_product')->pluck('ID');

        foreach ($productIds as $productId) {
            $soldQuantity = (float) DB::table('tbl_sold_item')
                ->where('Product_ID', $productId)
                ->sum('Quantity');

            $batches = DB::table('tbl_stock_in')
                ->where('Product_ID', $productId)
                ->orderBy('ID', 'asc')
                ->get(['ID', 'Quantity']);

            foreach ($batches as $batch) {
                $batchQuantity = (float) $batch->Quantity;
                $consumed = min($batchQuantity, max(0, $soldQuantity));
                $remaining = max(0, $batchQuantity - $consumed);

                DB::table('tbl_stock_in')
                    ->where('ID', $batch->ID)
                    ->update(['Remaining_Quantity' => $remaining]);

                $soldQuantity -= $consumed;
            }
        }

        if (Schema::hasColumn('tbl_product', 'Has_Expiration')) {
            Schema::table('tbl_product', function (Blueprint $table) {
                $table->dropColumn('Has_Expiration');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('tbl_product', 'Has_Expiration')) {
            Schema::table('tbl_product', function (Blueprint $table) {
                $table->boolean('Has_Expiration')
                    ->default(false)
                    ->after('Description');
            });
        }

        if (Schema::hasColumn('tbl_stock_in', 'Remaining_Quantity')) {
            Schema::table('tbl_stock_in', function (Blueprint $table) {
                $table->dropColumn('Remaining_Quantity');
            });
        }
    }
};
