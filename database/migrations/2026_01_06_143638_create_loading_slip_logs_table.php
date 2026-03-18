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
        Schema::create('loading_slip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_slip_id')->constrained('loading_slips')->onDelete('cascade');
            $table->foreignId('dispatch_qc_id')->nullable()->constrained('dispatch_qc')->onDelete('set null');
            $table->string('customer')->nullable();
            $table->string('commodity')->nullable();
            $table->decimal('so_qty', 15, 2)->nullable();
            $table->decimal('do_qty', 15, 2)->nullable();
            $table->string('factory')->nullable();
            $table->string('gala')->nullable();
            $table->integer('no_of_bags')->nullable();
            $table->decimal('bag_size', 15, 2)->nullable();
            $table->decimal('kilogram', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('labour')->nullable();
            $table->string('qc_remarks')->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_slip_logs');
    }
};
