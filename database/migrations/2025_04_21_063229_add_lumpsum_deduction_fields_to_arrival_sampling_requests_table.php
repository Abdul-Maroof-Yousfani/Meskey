<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLumpsumDeductionFieldsToArrivalSamplingRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->decimal('lumpsum_deduction_kgs', 10, 2)
                ->default(0)
                ->after('lumpsum_deduction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->dropColumn(['lumpsum_deduction_kgs']);
        });
    }
}
