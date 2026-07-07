<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightColumnsToPaymentRequestDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            // Add 3 new columns
            $table->decimal('access_weight', 15, 2)->default(0)->after('id'); // or after any specific column
            $table->decimal('exempted_weight', 15, 2)->default(0)->after('access_weight');
            $table->decimal('billing_weight', 15, 2)->default(0)->after('exempted_weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn(['access_weight', 'exempted_weight', 'billing_weight']);
        });
    }
}