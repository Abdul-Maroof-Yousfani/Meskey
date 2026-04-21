<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commercial_invoices')) {
            return;
        }

        Schema::table('commercial_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('commercial_invoices', 'selected_bill_of_lading_ids')) {
                $table->json('selected_bill_of_lading_ids')->nullable()->after('bill_of_lading_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('commercial_invoices')) {
            return;
        }

        Schema::table('commercial_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('commercial_invoices', 'selected_bill_of_lading_ids')) {
                $table->dropColumn('selected_bill_of_lading_ids');
            }
        });
    }
};
