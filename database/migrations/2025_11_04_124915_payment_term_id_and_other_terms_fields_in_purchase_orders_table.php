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
        Schema::table('purchase_orders', function (Blueprint $table) {

            $table->unsignedBigInteger('payment_term_id')->nullable()->after('supplier_id');
            $table->string('other_terms')->nullable()->after('description');

            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);

            $table->dropColumn('other_terms');
            $table->dropColumn('payment_term_id');
        });
    }
};
