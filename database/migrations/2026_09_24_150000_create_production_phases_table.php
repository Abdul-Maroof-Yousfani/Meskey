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
        Schema::create('production_phases', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Auto-seed default production phases
        $phases = [
            [
                'key' => 'phase_1',
                'name' => 'Phase 1 - Drying',
                'category' => 'Pre-Milling',
                'description' => 'Paddy drying cycles (Batch / Series / Sun Pero)',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'phase_2',
                'name' => 'Phase 2 - Steaming / Parboiling',
                'category' => 'Pre-Milling Cooking',
                'description' => 'Single/Double steaming, soaking and parboiling',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'phase_3',
                'name' => 'Phase 3 - Milling',
                'category' => 'Milling',
                'description' => 'Paddy husking, polishing, sorting, finished goods & by-products',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('production_phases')->insert($phases);

        // Fetch inserted IDs to initialize existing company_locations
        // $defaultIds = DB::table('production_phases')->pluck('id')->toArray();
        // if (!empty($defaultIds)) {
        //     DB::table('company_locations')
        //         ->whereNull('production_phases')
        //         ->update(['production_phases' => json_encode($defaultIds)]);
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_phases');
    }
};
