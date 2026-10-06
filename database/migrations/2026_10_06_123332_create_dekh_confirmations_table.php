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
        Schema::create('dekh_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_sale_inspection_id')->constrained('pre_sale_inspections')->cascadeOnDelete();
            $table->boolean('is_completed')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('am_approval_status', 50)->default('pending');
            $table->tinyInteger('am_change_made')->default(1);
            $table->string('vehicle_no', 100)->nullable();
            $table->timestamp('vehicle_assigned_at')->nullable();
            $table->foreignId('vehicle_assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dekh_confirmations');
    }
};
