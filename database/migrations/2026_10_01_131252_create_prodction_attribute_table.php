<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prodction_attribute', function (Blueprint $table) {
            $table->id();

            $table->string('key');
            $table->string('slug');
            $table->string('type');

            $table->boolean('for_general')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->unsignedBigInteger('created_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        // Dummy Data
        DB::table('prodction_attribute')->insert([
            [
                'key' => 'soaking_temperature',
                'slug' => 'soaking_temperature',
                'type' => 'temperature',
                'for_general' => false,
                'status' => 'active',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'soaking_duration',
                'slug' => 'soaking_duration',
                'type' => 'time',
                'for_general' => false,
                'status' => 'active',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'steam_duration',
                'slug' => 'steam_duration',
                'type' => 'time',
                'for_general' => false,
                'status' => 'active',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodction_attribute');
    }
};