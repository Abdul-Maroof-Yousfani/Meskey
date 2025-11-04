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
        Schema::table('purchase_order_data', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_id')->nullable()->after('supplier_id');
            $table->decimal('excise_duty', 11, 2)->nullable()->after('tax_id');
            $table->decimal('min_weight', 11, 2)->nullable()->after('excise_duty');
            $table->string('color')->nullable()->after('min_weight');
            $table->string('construction_per_square_inch', 11, 2)->nullable()->after('color');
            $table->string('size')->nullable()->after('construction_per_square_inch');
            $table->string('stitching')->nullable()->after('size');
            $table->string('printing_sample')->nullable()->after('stitching');

            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_data', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);

            $table->dropColumn('excise_duty');
            $table->dropColumn('tax_id');
            $table->dropColumn('min_weight');
            $table->dropColumn('color');
            $table->dropColumn('construction_per_square_inch');
            $table->dropColumn('size');
            $table->dropColumn('stitching');
            $table->dropColumn('printing_sample');
        });
    }
};
