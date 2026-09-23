<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. tbl_Category
        Schema::create('tbl_Category', function (Blueprint $table) {
            $table->id('ID');
            $table->string('Name');
            $table->timestamps();
        });

        // 2. tbl_Status
        Schema::create('tbl_Status', function (Blueprint $table) {
            $table->id('ID');
            $table->string('Name');
            $table->timestamps();
        });

        // 3. tbl_Payment_Method
        Schema::create('tbl_Payment_Method', function (Blueprint $table) {
            $table->id('ID');
            $table->string('Name');
            $table->timestamps();
        });

        // 4. tbl_Product
        Schema::create('tbl_Product', function (Blueprint $table) {
            $table->id('ID');
            $table->string('Name');
            $table->unsignedBigInteger('Category_ID');
            $table->unsignedBigInteger('Status_ID');
            $table->timestamps();

            $table->foreign('Category_ID')->references('ID')->on('tbl_Category')->onDelete('cascade');
            $table->foreign('Status_ID')->references('ID')->on('tbl_Status')->onDelete('cascade');
        });

        // 5. tbl_Stock_in
        Schema::create('tbl_Stock_in', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('Product_ID');
            $table->decimal('Quantity', 10, 2)->default(0);
            $table->decimal('Cost_Price', 10, 2)->default(0);
            $table->decimal('Retail_Price', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('Product_ID')->references('ID')->on('tbl_Product')->onDelete('cascade');
        });

        // 6. tbl_Sale
        Schema::create('tbl_Sale', function (Blueprint $table) {
            $table->id('ID');
            $table->dateTime('Date');
            $table->decimal('Total', 10, 2)->default(0);
            $table->unsignedBigInteger('User_ID');
            $table->unsignedBigInteger('Payment_Method_ID');
            $table->timestamps();

            $table->foreign('User_ID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('Payment_Method_ID')->references('ID')->on('tbl_Payment_Method')->onDelete('cascade');
        });

        // 7. tbl_Sold_Item
        Schema::create('tbl_Sold_Item', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('Product_ID');
            $table->decimal('Quantity', 10, 2)->default(1);
            $table->decimal('Total', 10, 2)->default(0);
            $table->unsignedBigInteger('Sale_ID');
            $table->timestamps();

            $table->foreign('Product_ID')->references('ID')->on('tbl_Product')->onDelete('cascade');
            $table->foreign('Sale_ID')->references('ID')->on('tbl_Sale')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_Sold_Item');
        Schema::dropIfExists('tbl_Sale');
        Schema::dropIfExists('tbl_Stock_in');
        Schema::dropIfExists('tbl_Product');
        Schema::dropIfExists('tbl_Payment_Method');
        Schema::dropIfExists('tbl_Status');
        Schema::dropIfExists('tbl_Category');
    }
};
