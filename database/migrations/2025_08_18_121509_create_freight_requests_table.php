<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFreightRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('freight_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arrival_ticket_id');
            $table->string('arrival_slip_id')->nullable();
            $table->string('arrival_slip_no')->nullable();

            $table->unsignedBigInteger('party_id');
            $table->decimal('contract_rate', 10, 2)->default(0);

            $table->decimal('exempt', 10, 2)->default(0);

            $table->decimal('freight_amount', 10, 2)->default(0);
            $table->decimal('freight_per_ton', 10, 2)->default(0);
            $table->decimal('loading_kanta', 10, 2)->default(0);
            $table->decimal('arrived_kanta', 10, 2)->default(0);

            $table->decimal('other_labour_positive', 10, 2)->default(0);
            $table->decimal('dehari_extra', 10, 2)->default(0);
            $table->decimal('market_comm', 10, 2)->default(0);

            $table->decimal('over_weight_ded', 10, 2)->default(0);
            $table->decimal('godown_penalty', 10, 2)->default(0);
            $table->decimal('other_labour_negative', 10, 2)->default(0);
            $table->decimal('extra_ded', 10, 2)->default(0);
            $table->decimal('commission_ded', 10, 2)->default(0);

            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('request_amount', 10, 2)->default(0);

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();

            $table->foreign('arrival_ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('freight_payments');
    }
}
