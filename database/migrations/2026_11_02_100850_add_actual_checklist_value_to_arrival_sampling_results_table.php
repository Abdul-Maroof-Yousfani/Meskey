<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('arrival_sampling_results')) {
            Schema::table('arrival_sampling_results', function (Blueprint $table) {
                if (!Schema::hasColumn('arrival_sampling_results', 'actual_checklist_value')) {
                    $table->string('actual_checklist_value')->nullable()->after('product_slab_type_id');
                }
            });

            // Copy checklist_value data to actual_checklist_value
            DB::statement("UPDATE arrival_sampling_results SET actual_checklist_value = checklist_value WHERE checklist_value IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('arrival_sampling_results')) {
            Schema::table('arrival_sampling_results', function (Blueprint $table) {
                if (Schema::hasColumn('arrival_sampling_results', 'actual_checklist_value')) {
                    $table->dropColumn('actual_checklist_value');
                }
            });
        }
    }
};
