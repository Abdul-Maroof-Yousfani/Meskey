<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_against')->nullable()->after('remarks')
                ->comment('Type of payment against (e.g., invoice, bill, expense)');
            
            $table->string('against_reference_no')->nullable()->after('payment_against')
                ->comment('Reference number of the document this payment is against');
                
            // Adding index for better query performance
            $table->index(['payment_against']);
            $table->index(['against_reference_no']);
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_against', 'against_reference_no']);
            $table->dropIndex(['payment_against']);
            $table->dropIndex(['against_reference_no']);
        });
    }
};