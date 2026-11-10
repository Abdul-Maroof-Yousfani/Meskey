<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Combined migration for:
 *  1. customers.owner_mobile_no nullable (international customers)
 *  2. bag_packings.value (packing size in kg)
 *  3. products.hs_code
 *  4. export_orders.product_hs_code
 *  5. export_orders.fumigation_dosage (renamed from fumigation_tablet if it exists)
 *  6. export_order_packing_sub_items.packing_size
 *  7. brokers.broker_type (local / international)
 *  8. c_freights.containers, container_size, remarks
 *  9. loading_slips.extra_bags
 * 10. loading_program_items.dosage
 * 11. delivery_order.fumigation_tablets renamed to fumigation_dosage
 *
 * Every step checks first, so it is safe to run on a database
 * where some of these changes already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. International customers may not have an owner mobile number
        if (Schema::hasColumn('customers', 'owner_mobile_no')) {
            DB::statement('ALTER TABLE customers MODIFY owner_mobile_no VARCHAR(255) NULL');
        }

        // 2. Packing size in kg, used to fill "Bag Size (kg)" when a packing is selected
        if (Schema::hasTable('bag_packings')) {
            if (!Schema::hasColumn('bag_packings', 'value')) {
                Schema::table('bag_packings', function (Blueprint $table) {
                    $table->decimal('value', 10, 2)->nullable()->after('name');
                });
            }

            // Fill existing rows from their name, e.g. "50 kg" => 50
            DB::table('bag_packings')->whereNull('value')->get(['id', 'name'])->each(function ($row) {
                if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*kg\b/i', $row->name, $m)) {
                    DB::table('bag_packings')->where('id', $row->id)->update(['value' => $m[1]]);
                }
            });
        }

        // 3. Nullable in DB for existing products; the product form makes it required
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'hs_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('hs_code')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('export_orders')) {
            // 4. HS code copied from products.hs_code so the order keeps the code it was made with.
            // Named product_hs_code so it does not clash with the hsCode relation (hs_code_id).
            if (!Schema::hasColumn('export_orders', 'product_hs_code')) {
                Schema::table('export_orders', function (Blueprint $table) {
                    $table->string('product_hs_code')->nullable()->after('hs_code_id');
                });
            }

            // 5. Fumigation dosage (optional). Rename the old fumigation_tablet column if present.
            if (!Schema::hasColumn('export_orders', 'fumigation_dosage')) {
                if (Schema::hasColumn('export_orders', 'fumigation_tablet')) {
                    DB::statement('ALTER TABLE export_orders CHANGE fumigation_tablet fumigation_dosage VARCHAR(100) NULL');
                } else {
                    Schema::table('export_orders', function (Blueprint $table) {
                        $table->string('fumigation_dosage', 100)->nullable()->after('fumigation_type');
                    });
                }
            }
        }

        // 6. Master bag size in kg; primary bags = packing_size / main bag size
        if (Schema::hasTable('export_order_packing_sub_items') && !Schema::hasColumn('export_order_packing_sub_items', 'packing_size')) {
            Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
                $table->decimal('packing_size', 10, 2)->nullable()->after('bag_size_id');
            });
        }

        // 7. Broker type
        if (Schema::hasTable('brokers') && !Schema::hasColumn('brokers', 'broker_type')) {
            Schema::table('brokers', function (Blueprint $table) {
                $table->enum('broker_type', ['local', 'international'])->default('local')->after('unique_no');
            });
        }

        // 8. C Freight: quantity split into containers + container_size, plus remarks
        if (Schema::hasTable('c_freights')) {
            // Place the new columns right before "quantity"
            $columns = Schema::getColumnListing('c_freights');
            $quantityIndex = array_search('quantity', $columns);
            $afterColumn = $quantityIndex > 0 ? $columns[$quantityIndex - 1] : null;

            Schema::table('c_freights', function (Blueprint $table) use ($afterColumn) {
                if (!Schema::hasColumn('c_freights', 'containers')) {
                    $column = $table->integer('containers')->nullable();
                    if ($afterColumn) {
                        $column->after($afterColumn);
                    }
                }
                if (!Schema::hasColumn('c_freights', 'container_size')) {
                    $table->string('container_size')->nullable()->after('containers');
                }
                if (!Schema::hasColumn('c_freights', 'remarks')) {
                    $table->text('remarks')->nullable()->after('eta');
                }
            });
        }

        // 9. Extra bags on loading slips
        if (Schema::hasTable('loading_slips') && !Schema::hasColumn('loading_slips', 'extra_bags')) {
            Schema::table('loading_slips', function (Blueprint $table) {
                $table->integer('extra_bags')->nullable()->after('empty_bags');
            });
        }

        // 10. Fumigation dosage, entered on the export Outer Items form
        if (Schema::hasTable('loading_program_items') && !Schema::hasColumn('loading_program_items', 'dosage')) {
            Schema::table('loading_program_items', function (Blueprint $table) {
                $table->integer('dosage')->nullable();
            });
        }

        // 11. Delivery order: fumigation_tablets renamed to fumigation_dosage
        if (Schema::hasTable('delivery_order')
            && Schema::hasColumn('delivery_order', 'fumigation_tablets')
            && !Schema::hasColumn('delivery_order', 'fumigation_dosage')) {
            DB::statement('ALTER TABLE delivery_order CHANGE fumigation_tablets fumigation_dosage VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_order')
            && Schema::hasColumn('delivery_order', 'fumigation_dosage')
            && !Schema::hasColumn('delivery_order', 'fumigation_tablets')) {
            DB::statement('ALTER TABLE delivery_order CHANGE fumigation_dosage fumigation_tablets VARCHAR(255) NULL');
        }

        $this->dropColumns('loading_program_items', ['dosage']);
        $this->dropColumns('loading_slips', ['extra_bags']);
        $this->dropColumns('c_freights', ['containers', 'container_size', 'remarks']);
        $this->dropColumns('brokers', ['broker_type']);
        $this->dropColumns('export_order_packing_sub_items', ['packing_size']);
        $this->dropColumns('export_orders', ['fumigation_dosage', 'fumigation_tablet', 'product_hs_code']);
        $this->dropColumns('products', ['hs_code']);
        $this->dropColumns('bag_packings', ['value']);

        if (Schema::hasColumn('customers', 'owner_mobile_no')) {
            DB::statement("UPDATE customers SET owner_mobile_no = '' WHERE owner_mobile_no IS NULL");
            DB::statement('ALTER TABLE customers MODIFY owner_mobile_no VARCHAR(255) NOT NULL');
        }
    }

    private function dropColumns(string $tableName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($tableName, $column)));

        if (!empty($existing)) {
            Schema::table($tableName, function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
