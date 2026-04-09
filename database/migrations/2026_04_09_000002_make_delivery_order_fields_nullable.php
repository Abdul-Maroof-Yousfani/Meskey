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
        Schema::table('delivery_order', function (Blueprint $table) {
            // Make all fields properly nullable
            $table->unsignedBigInteger('so_id')->nullable()->change();
            $table->float('advance_amount')->nullable()->default(0)->change();
            $table->float('withhold_amount')->nullable()->default(0)->change();
            $table->date('dispatch_date')->nullable()->change();
            $table->unsignedBigInteger('location_id')->nullable()->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->string('reference_no')->nullable()->change();
            $table->enum('sauda_type', ['pohanch', 'x-mill'])->nullable()->change();
            $table->string('am_approval_status')->nullable()->default('pending')->change();
            $table->unsignedBigInteger('payment_term_id')->nullable()->change();
            $table->string('am_change_made')->nullable()->default('1')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert changes if needed
    }
};
