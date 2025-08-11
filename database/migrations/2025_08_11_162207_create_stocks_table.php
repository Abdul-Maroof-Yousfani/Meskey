<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('voucher_type', ['grn', 'gdn', 'sale_return', 'purchase_return']);
            $table->string('voucher_no');
            $table->decimal('qty', 12, 2);
            $table->enum('type', ['stock-in', 'stock-out']);
            $table->text('narration')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('avg_price_per_kg', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
};
