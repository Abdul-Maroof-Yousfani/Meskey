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
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('pre_sale_inspections', 'arrival_location_id')) {
                $table->foreignId('arrival_location_id')
                    ->nullable()
                    ->after('location_id')
                    ->constrained('arrival_locations')
                    ->nullOnDelete();
            }
        });

        // Backfill existing inspections from their first item arrival_location_id if available
        $inspections = DB::table('pre_sale_inspections')->whereNull('arrival_location_id')->get();
        foreach ($inspections as $ins) {
            $firstItem = DB::table('pre_sale_inspection_items')
                ->where('pre_sale_inspection_id', $ins->id)
                ->whereNotNull('arrival_location_id')
                ->first();
            if ($firstItem) {
                DB::table('pre_sale_inspections')
                    ->where('id', $ins->id)
                    ->update(['arrival_location_id' => $firstItem->arrival_location_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('pre_sale_inspections', 'arrival_location_id')) {
                $table->dropForeign(['arrival_location_id']);
                $table->dropColumn('arrival_location_id');
            }
        });
    }
};
