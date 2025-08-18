<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn('module_type');
        });

        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->enum('module_type', ['ticket', 'purchase_order', 'freight_payment'])
                ->after('notes')
                ->default('purchase_order');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('module_type');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->enum('module_type', ['ticket', 'purchase_order', 'freight_payment'])
                ->after('payment_request_data_id')
                ->default('purchase_order');
        });
    }

    public function down()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn('module_type');
        });

        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->enum('module_type', ['ticket', 'purchase_order'])
                ->after('notes')
                ->default('purchase_order');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('module_type');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->enum('module_type', ['ticket', 'purchase_order'])
                ->after('payment_request_data_id')
                ->default('purchase_order');
        });
    }
};
