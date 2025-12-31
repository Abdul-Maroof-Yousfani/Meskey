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
        Schema::create('sales_qc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_program_item_id')->constrained('loading_program_items')->onDelete('cascade');
            $table->string('customer');
            $table->string('commodity');
            $table->decimal('so_qty', 10, 2);
            $table->decimal('do_qty', 10, 2);
            $table->string('factory');
            $table->string('gala');
            $table->text('qc_remarks')->nullable();
            $table->enum('status', ['accept', 'reject']);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_qc');
    }
};
