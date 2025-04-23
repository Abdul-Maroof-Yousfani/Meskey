<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLumpsumFieldsToArrivalTicketTable extends Migration
{
    public function up()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->decimal('lumpsum_deduction', 10, 2)
                ->default(0)
                ->after('arrived_net_weight');

            $table->decimal('lumpsum_deduction_kgs', 10, 2)
                ->default(0)
                ->after('lumpsum_deduction');

            $table->tinyInteger('is_lumpsum_deduction')
                ->default(0)
                ->after('lumpsum_deduction')
                ->comment('Switch state for lumpsum deduction');
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'lumpsum_deduction',
                'lumpsum_deduction_kgs',
                'is_lumpsum_deduction'
            ]);
        });
    }
}
