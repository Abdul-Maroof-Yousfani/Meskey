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
        Schema::table('loading_slips', function (Blueprint $table) {
            if (!Schema::hasColumn('loading_slips', 'seal_no')) {
                $table->string('seal_no')->nullable()->after('kilogram');
            }
        });

        Schema::create('loading_slip_stacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loading_slip_id');
            $table->string('bag_type')->nullable();
            $table->string('packing_size')->nullable();
            $table->string('input_size')->nullable();
            $table->timestamps();

            $table->foreign('loading_slip_id')->references('id')->on('loading_slips')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_slip_stacks');
        Schema::table('loading_slips', function (Blueprint $table) {
            $table->dropColumn('seal_no');
        });
    }
};
