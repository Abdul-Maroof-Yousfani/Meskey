<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_request_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->unsignedBigInteger('category_id');
            $table->integer("brand_id");
            $table->unsignedBigInteger('item_id');
            $table->decimal('qty', 15, 2);
            $table->decimal('approved_qty', 15, 2);
            $table->decimal('min_weight', 11, 2)->nullable();
            $table->string('color')->nullable();
            $table->string('construction_per_square_inch', 11, 2);
            $table->string('size')->nullable();
            $table->string('stitching')->nullable();
            $table->string('printing_sample')->nullable();
        
            $table->enum('quotation_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->enum('po_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->enum('status', ['1', '0'])->default('1')->comment('1 = active, 0 = inactive');
            $table->text('remarks')->nullable();





            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_data');
    }
};
