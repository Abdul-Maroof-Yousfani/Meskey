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
        // On some systems (like the user's live server), this constraint exists and causes errors for the Export module.
        // We drop it to allow do_data_id to point to different tables (Sales vs Export).
        try {
            Schema::table('delivery_challan_data', function (Blueprint $table) {
                $table->dropForeign(['do_data_id']);
            });
        } catch (\Exception $e) {
            // If it doesn't exist, just ignore the error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_challan_data', function (Blueprint $table) {
            try {
                $table->foreign('do_data_id')->references('id')->on('delivery_order_data')->onDelete('cascade');
            } catch (\Exception $e) {
                // Ignore if cannot re-add
            }
        });
    }
};
