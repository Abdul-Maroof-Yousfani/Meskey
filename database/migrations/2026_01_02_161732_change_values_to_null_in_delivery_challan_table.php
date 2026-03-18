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
        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->string("reference_number")->nullable()->change();
            $table->string("labour")->nullable()->change();
            $table->string("labour_amount")->nullable()->change();
            $table->string("transporter")->nullable()->change();
            $table->string("transporter_amount")->nullable()->change();
            $table->string("inhouse-weighbridge")->nullable()->change();
            $table->string("weighbridge-amount")->nullable()->change();
            $table->string("remarks")->nullable()->change();
            // $table->string("truck_no")->nullable()->change();
            // $table->string("bilty_no")->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_challan', function (Blueprint $table) {
            //
        });
    }
};
