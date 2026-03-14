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
        Schema::table('production_vouchers', function (Blueprint $table) {

            $table->unsignedBigInteger('sub_location_id')->default(0)->after('location_id');
            $table->decimal('net_total_input', 10, 2)->nullable()->default(0)->after('sub_location_id');
            $table->decimal('net_total_output', 10, 2)->nullable()->default(0)->after('net_total_input');
            $table->decimal('labour_charges_per_kg', 10, 2)->nullable()->default(0)->after('net_total_output');
            $table->decimal('total_labour_charges', 10, 2)->nullable()->default(0)->after('labour_charges_per_kg');
            $table->decimal('labour_deduction', 10, 2)->nullable()->default(0)->after('total_labour_charges');
            $table->text('labour_deduction_remarks')->nullable()->after('labour_deduction');
            $table->decimal('labour_net_amount', 10, 2)->nullable()->default(0)->after('labour_deduction_remarks');

        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_vouchers', function (Blueprint $table) {
            $table->dropColumn('sub_location_id');
            $table->dropColumn('net_total_input');
            $table->dropColumn('net_total_output');
            $table->dropColumn('labour_charges_per_kg');
            $table->dropColumn('total_labour_charges');
            $table->dropColumn('labour_deduction');
            $table->dropColumn('labour_remarks');
            $table->dropColumn('labour_net_amount');
        });
    }
};
