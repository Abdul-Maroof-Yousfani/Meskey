<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Export loading slip line items: har truck me kaunsi packaging (DO packing row) ke kitne bags,
 * empty bags aur extra bags gaye. loading_slips par sirf in ka total rehta hai.
 *
 * Do kaam (ek hi migration me):
 *  1. Table nahi hai -> naye structure ke saath bana do.
 *  2. Table pehle se kisi purane structure me hai (packing_item_id column, bag_type_id / brand_id nahi,
 *     MyISAM engine) -> usi table ko sahi structure me le aao:
 *      - engine InnoDB (transaction rollback aur foreign key ke liye)
 *      - jin rows ka loading slip mojood nahi un ko hata do (warna foreign key nahi lagti)
 *      - packing_item_id -> delivery_order_packing_item_id
 *      - bag_type_id, brand_id add
 *      - loading_slip_id par foreign key (slip delete ho to us ki lines bhi)
 */
return new class extends Migration
{
    private string $table = 'export_loading_slip_items';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            $this->createTable();
            return;
        }

        $this->fixExistingTable();
    }

    private function createTable(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loading_slip_id')->index();
            $table->unsignedBigInteger('delivery_order_id')->nullable()->index();
            // export_delivery_order_packing_items.id (approved DOs keep their packing rows)
            $table->unsignedBigInteger('delivery_order_packing_item_id')->nullable()->index('elsi_do_packing_item_idx');
            $table->unsignedBigInteger('bag_type_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->decimal('bag_size', 10, 2)->default(0);
            $table->unsignedInteger('no_of_bags')->default(0);
            $table->unsignedInteger('empty_bags')->default(0);
            $table->unsignedInteger('extra_bags')->default(0);
            $table->decimal('kilogram', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('loading_slip_id', 'elsi_loading_slip_fk')
                ->references('id')->on('loading_slips')
                ->cascadeOnDelete();
        });
    }

    private function fixExistingTable(): void
    {
        $engine = DB::selectOne("SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$this->table]);
        if ($engine && strtoupper((string) $engine->engine) !== 'INNODB') {
            DB::statement("ALTER TABLE {$this->table} ENGINE=InnoDB");
        }

        // Lines whose loading slip no longer exists
        DB::statement("DELETE i FROM {$this->table} i LEFT JOIN loading_slips s ON s.id = i.loading_slip_id WHERE s.id IS NULL");

        if (Schema::hasColumn($this->table, 'packing_item_id') && !Schema::hasColumn($this->table, 'delivery_order_packing_item_id')) {
            DB::statement("ALTER TABLE {$this->table} CHANGE packing_item_id delivery_order_packing_item_id BIGINT UNSIGNED NULL");
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'bag_type_id')) {
                $table->unsignedBigInteger('bag_type_id')->nullable()->after('delivery_order_packing_item_id');
            }
            if (!Schema::hasColumn($this->table, 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('bag_type_id');
            }
        });

        $hasForeignKey = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'loading_slip_id' AND REFERENCED_TABLE_NAME = 'loading_slips'",
            [$this->table]
        );
        if ((int) ($hasForeignKey->c ?? 0) === 0) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->foreign('loading_slip_id', 'elsi_loading_slip_fk')
                    ->references('id')->on('loading_slips')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
