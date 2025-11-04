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
        Schema::table('purchase_request_data', function (Blueprint $table) {
            $table->decimal('min_weight', 11, 2)->nullable()->after('approved_qty');
            $table->string('color')->nullable()->after('min_weight');
            $table->string('construction_per_square_inch', 11, 2)->nullable()->after('color');
            $table->string('size')->nullable()->after('construction_per_square_inch');
            $table->string('stitching')->nullable()->after('size');
            $table->string('printing_sample')->nullable()->after('stitching');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_data', function (Blueprint $table) {
            $table->dropColumn('min_weight');
            $table->dropColumn('color');
            $table->dropColumn('construction_per_square_inch');
            $table->dropColumn('size');
            $table->dropColumn('stitching');
            $table->dropColumn('printing_sample');
        });
    }
};
