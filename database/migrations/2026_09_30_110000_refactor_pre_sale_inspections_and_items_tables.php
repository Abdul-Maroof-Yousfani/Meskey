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
        // 1. Add single location_id to pre_sale_inspections
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('date')->constrained('company_locations')->nullOnDelete();
        });

        // 2. Add Factory (arrival_location_id) and Section (arrival_sub_location_id) to pre_sale_inspection_items
        Schema::table('pre_sale_inspection_items', function (Blueprint $table) {
            $table->foreignId('arrival_location_id')->nullable()->after('item_id')->constrained('arrival_locations')->nullOnDelete();
            $table->foreignId('arrival_sub_location_id')->nullable()->after('arrival_location_id')->constrained('arrival_sub_locations')->nullOnDelete();
        });

        // 3. Migrate any existing data
        $inspections = DB::table('pre_sale_inspections')->get();
        foreach ($inspections as $ins) {
            $locId = null;
            if (!empty($ins->locations)) {
                $locs = json_decode($ins->locations, true);
                if (is_array($locs) && count($locs) > 0) {
                    $locId = (int)$locs[0];
                }
            }
            if ($locId) {
                DB::table('pre_sale_inspections')->where('id', $ins->id)->update(['location_id' => $locId]);
            }

            if (!empty($ins->arrival_location_id) || !empty($ins->arrival_sub_location_id)) {
                DB::table('pre_sale_inspection_items')
                    ->where('pre_sale_inspection_id', $ins->id)
                    ->whereNull('arrival_location_id')
                    ->update([
                        'arrival_location_id' => $ins->arrival_location_id,
                        'arrival_sub_location_id' => $ins->arrival_sub_location_id,
                    ]);
            }
        }

        // 4. Drop useless columns from pre_sale_inspections
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['arrival_location_id']);
            $table->dropForeign(['arrival_sub_location_id']);
            $table->dropColumn([
                'item_id',
                'locations',
                'factories',
                'sections',
                'arrival_location_id',
                'arrival_sub_location_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('locations')->nullable();
            $table->json('factories')->nullable();
            $table->json('sections')->nullable();
            $table->foreignId('arrival_location_id')->nullable()->constrained('arrival_locations')->nullOnDelete();
            $table->foreignId('arrival_sub_location_id')->nullable()->constrained('arrival_sub_locations')->nullOnDelete();
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('pre_sale_inspection_items', function (Blueprint $table) {
            $table->dropForeign(['arrival_location_id']);
            $table->dropForeign(['arrival_sub_location_id']);
            $table->dropColumn(['arrival_location_id', 'arrival_sub_location_id']);
        });
    }
};
