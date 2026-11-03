<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('product_head_types')) {
            Schema::create('product_head_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name', 150);
                $table->string('slug', 100)->unique();
                $table->integer('sort_order')->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });

            // Seed default head types
            DB::table('product_head_types')->insert([
                [
                    'name'         => 'Head Product',
                    'slug'         => 'head',
                    'sort_order'   => 1,
                    'status'       => 'active',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'name'         => 'B2 Product',
                    'slug'         => 'b2',
                    'sort_order'   => 2,
                    'status'       => 'active',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'name'         => 'By Product',
                    'slug'         => 'by',
                    'sort_order'   => 3,
                    'status'       => 'active',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_head_types');
    }
};
