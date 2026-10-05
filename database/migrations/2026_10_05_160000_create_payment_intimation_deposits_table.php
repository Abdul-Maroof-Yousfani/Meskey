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
        // 1. Add bank column to payment_intimations to store JSON of bank names
        if (Schema::hasTable('payment_intimations') && !Schema::hasColumn('payment_intimations', 'bank')) {
            Schema::table('payment_intimations', function (Blueprint $table) {
                $table->text('bank')->nullable()->after('sale_order_id');
            });
        }

        // 2. Create child table payment_intimation_deposits
        if (!Schema::hasTable('payment_intimation_deposits')) {
            Schema::create('payment_intimation_deposits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_intimation_id');
                $table->unsignedBigInteger('bank_id')->nullable();
                $table->decimal('payment_deposit', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('payment_intimation_id')
                    ->references('id')
                    ->on('payment_intimations')
                    ->onDelete('cascade');

                $table->foreign('bank_id')
                    ->references('id')
                    ->on('banks')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intimation_deposits');

        if (Schema::hasTable('payment_intimations') && Schema::hasColumn('payment_intimations', 'bank')) {
            Schema::table('payment_intimations', function (Blueprint $table) {
                $table->dropColumn('bank');
            });
        }
    }
};
