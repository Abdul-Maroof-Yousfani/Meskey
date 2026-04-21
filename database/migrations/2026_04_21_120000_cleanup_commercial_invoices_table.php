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
            $foreignColumns = [
                'company_id',
                'customer_id',
            ];

            foreach ($foreignColumns as $column) {
                if (Schema::hasColumn('commercial_invoices', $column)) {
                    $table->dropForeign([$column]);
                }
            }

            $dropColumns = [
                'company_id',
                'customer_id',
                'proforma_no',
                'lc_no',
                'lc_date',
                'ship_name',
                'bill_of_lading_no',
                'bill_of_lading_date',
                'master_bl',
                'consigned_details',
                'e_forms',
                'snapshot_data',
                'goods_summary',
            ];

            $existingColumns = array_values(array_filter($dropColumns, fn ($column) => Schema::hasColumn('commercial_invoices', $column)));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('commercial_invoices')) {
            return;
        }

        Schema::table('commercial_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('commercial_invoices', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('bill_of_lading_id')->constrained('companies')->nullOnDelete();
            }

            if (!Schema::hasColumn('commercial_invoices', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('company_id')->constrained('customers')->nullOnDelete();
            }

            if (!Schema::hasColumn('commercial_invoices', 'proforma_no')) {
                $table->string('proforma_no')->nullable()->after('invoice_date');
            }

            if (!Schema::hasColumn('commercial_invoices', 'lc_no')) {
                $table->string('lc_no')->nullable()->after('invoice_no');
            }

            if (!Schema::hasColumn('commercial_invoices', 'lc_date')) {
                $table->date('lc_date')->nullable()->after('lc_no');
            }

            if (!Schema::hasColumn('commercial_invoices', 'ship_name')) {
                $table->string('ship_name')->nullable()->after('lc_date');
            }

            if (!Schema::hasColumn('commercial_invoices', 'bill_of_lading_no')) {
                $table->string('bill_of_lading_no')->nullable()->after('ship_name');
            }

            if (!Schema::hasColumn('commercial_invoices', 'bill_of_lading_date')) {
                $table->date('bill_of_lading_date')->nullable()->after('bill_of_lading_no');
            }

            if (!Schema::hasColumn('commercial_invoices', 'master_bl')) {
                $table->string('master_bl')->nullable()->after('bill_of_lading_date');
            }

            if (!Schema::hasColumn('commercial_invoices', 'consigned_details')) {
                $table->text('consigned_details')->nullable()->after('master_bl');
            }

            if (!Schema::hasColumn('commercial_invoices', 'e_forms')) {
                $table->json('e_forms')->nullable()->after('consigned_details');
            }

            if (!Schema::hasColumn('commercial_invoices', 'snapshot_data')) {
                $table->json('snapshot_data')->nullable()->after('e_forms');
            }

            if (!Schema::hasColumn('commercial_invoices', 'goods_summary')) {
                $table->json('goods_summary')->nullable()->after('snapshot_data');
            }
        });
    }
};
