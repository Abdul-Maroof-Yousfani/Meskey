<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indicative_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('company_locations')->onDelete('cascade');
            $table->foreignId('type_id')->constrained('sauda_types')->onDelete('cascade');

            $table->year('crop_year');
            $table->string('delivery_condition');

            $table->decimal('cash_rate', 10, 2)->nullable();
            $table->integer('cash_days')->nullable();

            $table->decimal('credit_rate', 10, 2)->nullable();
            $table->integer('credit_days')->nullable();

            $table->string('others')->nullable();
            $table->string('remarks')->nullable();

            $table->time('time')->nullable();
            $table->foreignId('created_by')->constrained('users');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicative_prices');
    }
};
