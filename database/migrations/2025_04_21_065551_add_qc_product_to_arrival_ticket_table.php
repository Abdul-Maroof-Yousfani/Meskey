<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('qc_product')->nullable()->after('product_id');

            $table->foreign('qc_product')
                ->references('id')
                ->on('products')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropForeign(['qc_product']);
            $table->dropColumn('qc_product');
        });
    }
};
