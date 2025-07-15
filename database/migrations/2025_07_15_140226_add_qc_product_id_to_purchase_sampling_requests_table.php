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
        Schema::table('purchase_sampling_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('qc_product_id')->nullable()->after('arrival_product_id');
            $table->foreign('qc_product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_sampling_requests', function (Blueprint $table) {
            $table->dropForeign(['qc_product_id']);
            $table->dropColumn('qc_product_id');
        });
    }
};
