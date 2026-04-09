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
        Schema::table('delivery_order', function (Blueprint $table) {
            // New columns for export
            $table->string('type')->default('sale_order')->after('id');
            $table->foreignId('export_order_id')->nullable()->after('type')->constrained('export_orders')->nullOnDelete();
            $table->foreignId('export_form_e_id')->nullable()->after('export_order_id')->constrained('export_form_es')->nullOnDelete();
            
            if (!Schema::hasColumn('delivery_order', 'remarks')) {
                $table->text('remarks')->nullable()->after('line_desc');
            }
        });
        
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->foreignId("so_id")->nullable()->change();
            $table->float("advance_amount")->nullable()->default(0)->change();
            $table->float("withhold_amount")->nullable()->default(0)->change();
            $table->date("dispatch_date")->nullable()->change();
            $table->foreignId("location_id")->nullable()->change();
            $table->foreignId("company_id")->nullable()->change();
            $table->string("reference_no")->nullable()->change();
            $table->enum("sauda_type", ["pohanch", "x-mill"])->nullable()->change();
            $table->string("am_approval_status")->nullable()->default("pending")->change();
            $table->foreignId("payment_term_id")->nullable()->change();
            $table->string("am_change_made")->nullable()->default(1)->change();
            
            // Keeping these in case they also need to be explicitly nullable to prevent constraint errors
            $table->foreignId("arrival_id")->nullable()->change();
            $table->foreignId("subarrival_id")->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->dropForeign(['export_order_id']);
            $table->dropForeign(['export_form_e_id']);
            $table->dropColumn(['type', 'export_order_id', 'export_form_e_id', 'remarks']);
        });
    }
};      
    