<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arrival_compulsory_qc_params', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('properties')
                ->nullable()
                ->comment('Additional properties in JSON format');
            $table->tinyInteger('calculation_base_type')
                ->default(3)
                ->nullable()
                ->comment('1 = Percentage, 2 = KG, 3 = Price, 4 = Quantity, etc...');

            $table->enum('type', ['dropdown', 'text']);
            $table->json('options')->nullable(); // only for dropdowns
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_compulsory_qc_params');
    }
};
