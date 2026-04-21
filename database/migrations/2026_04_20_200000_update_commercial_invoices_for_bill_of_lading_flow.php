<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commercial_invoices')) {
            Schema::create('commercial_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('export_order_id')->nullable()->constrained('export_orders')->nullOnDelete();
                $table->foreignId('bill_of_lading_id')->nullable()->constrained('bill_of_ladings')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('commercial_invoice_no')->nullable();
                $table->date('invoice_date')->nullable();
                $table->string('invoice_no')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('commercial_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('commercial_invoices', 'bill_of_lading_id')) {
                $table->foreignId('bill_of_lading_id')->nullable()->after('export_order_id')->constrained('bill_of_ladings')->nullOnDelete();
            }

            if (!Schema::hasColumn('commercial_invoices', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('invoice_no')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('commercial_invoices', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('commercial_invoices', 'bill_of_lading_id')) {
                $table->dropConstrainedForeignId('bill_of_lading_id');
            }
        });
    }
};
