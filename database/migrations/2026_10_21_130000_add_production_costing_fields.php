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
        // 1. Modify stocks.voucher_type to include 'production_voucher'
        try {
            DB::statement("ALTER TABLE stocks MODIFY COLUMN voucher_type ENUM('grn','gdn','sale_return','purchase_return','delivery_challan','qc','production_voucher') NOT NULL");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('stocks.voucher_type alter warning: ' . $e->getMessage());
        }

        // 2. Add costing columns to production_vouchers
        Schema::table('production_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('production_vouchers', 'raw_material_cost')) {
                $table->decimal('raw_material_cost', 15, 2)->default(0)->after('produced_qty_kg');
            }
            if (!Schema::hasColumn('production_vouchers', 'milling_cost')) {
                $table->decimal('milling_cost', 15, 2)->default(0)->after('raw_material_cost');
            }
            if (!Schema::hasColumn('production_vouchers', 'by_product_cost')) {
                $table->decimal('by_product_cost', 15, 2)->default(0)->after('milling_cost');
            }
            if (!Schema::hasColumn('production_vouchers', 'net_finished_good_cost')) {
                $table->decimal('net_finished_good_cost', 15, 2)->default(0)->after('by_product_cost');
            }
            if (!Schema::hasColumn('production_vouchers', 'finished_good_rate')) {
                $table->decimal('finished_good_rate', 15, 4)->default(0)->after('net_finished_good_cost');
            }
            if (!Schema::hasColumn('production_vouchers', 'milling_rate_id')) {
                $table->unsignedBigInteger('milling_rate_id')->nullable()->after('finished_good_rate');
            }
            if (!Schema::hasColumn('production_vouchers', 'by_product_rate_id')) {
                $table->unsignedBigInteger('by_product_rate_id')->nullable()->after('milling_rate_id');
            }
        });

        // 3. Add rate and amount to production_inputs
        Schema::table('production_inputs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_inputs', 'rate')) {
                $table->decimal('rate', 15, 4)->default(0)->after('qty');
            }
            if (!Schema::hasColumn('production_inputs', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0)->after('rate');
            }
        });

        // 4. Add rate, amount and cost_type to production_outputs
        Schema::table('production_outputs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_outputs', 'rate')) {
                $table->decimal('rate', 15, 4)->default(0)->after('qty');
            }
            if (!Schema::hasColumn('production_outputs', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0)->after('rate');
            }
            if (!Schema::hasColumn('production_outputs', 'cost_type')) {
                $table->string('cost_type', 50)->nullable()->after('amount');
            }
        });

        // 5. Create production_voucher_variables table for variable overhead breakdown
        if (!Schema::hasTable('production_voucher_variables')) {
            Schema::create('production_voucher_variables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('production_voucher_id');
                $table->unsignedBigInteger('variable_id');
                $table->decimal('rate', 10, 4)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedBigInteger('account_id')->nullable();
                $table->timestamps();

                $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->onDelete('cascade');
                $table->foreign('variable_id')->references('id')->on('variables')->onDelete('cascade');
            });
        }

        // 6. Ensure transaction_voucher_types has Production Voucher
        $hasPv = DB::table('transaction_voucher_types')->where('code', 'PRO')->orWhere('code', 'PV')->first();
        if (!$hasPv) {
            DB::table('transaction_voucher_types')->insert([
                'name' => 'Production Voucher',
                'code' => 'PRO',
                'description' => 'Automated JV for Production Costing & Output Recoveries',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_voucher_variables');

        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropColumn(['rate', 'amount', 'cost_type']);
        });

        Schema::table('production_inputs', function (Blueprint $table) {
            $table->dropColumn(['rate', 'amount']);
        });

        Schema::table('production_vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'raw_material_cost',
                'milling_cost',
                'by_product_cost',
                'net_finished_good_cost',
                'finished_good_rate',
                'milling_rate_id',
                'by_product_rate_id',
            ]);
        });
    }
};
