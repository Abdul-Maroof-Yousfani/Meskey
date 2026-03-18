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
        Schema::create('sales_second_weighbridges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // replaced delivery_order_id with loading_slip_id
            $table->foreignId('loading_slip_id')
                ->constrained('loading_slips')
                ->cascadeOnDelete();

            $table->decimal('first_weight', 10, 2);
            $table->decimal('second_weight', 10, 2);
            $table->decimal('net_weight', 10, 2);

            $table->text('remark')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_second_weighbridges');
    }
};
