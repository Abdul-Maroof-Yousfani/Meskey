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
        Schema::table('delivery_challans', function (Blueprint $table) {
            // 1. Drop the foreign key using the EXACT constraint name
            // The name is almost always: table_column_foreign
            $table->dropForeign('delivery_challans_arrival_id_foreign');

            // // 2. Now drop the index (safe if already gone)
            // try {
            //     $table->dropIndex('delivery_challans_arrival_id_foreign');
            // } catch (\Exception $e) {
            //     // Ignore if index doesn't exist
            // }

            // // 3. NOW change the column type
            // $table->string('arrival_id')->change();

            // // Repeat for company_id if needed
            // // $table->dropForeign('delivery_challans_company_id_foreign');
            // // $table->dropIndex('delivery_challans_company_id_foreign');
            // // $table->string('company_id')->change();

            // // Add new column safely
            // if (!Schema::hasColumn('delivery_challans', 'section_id')) {
            //     $table->string('section_id')->nullable();
            // }                                                                                     
        });
    }
        
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_challans', function (Blueprint $table) {
            //
        });
    }
};
