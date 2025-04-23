<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialPurchaseRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('raw_material_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('company_location_id')->nullable();
            $table->decimal('quantity', 10, 2); // Adjust precision if needed
            $table->date('expected_delivery_date');
            $table->decimal('rate_per_kg', 10, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');

            $table->foreign('company_location_id')->references('id')->on('company_locations');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('raw_material_purchase_requests');
    }
}
