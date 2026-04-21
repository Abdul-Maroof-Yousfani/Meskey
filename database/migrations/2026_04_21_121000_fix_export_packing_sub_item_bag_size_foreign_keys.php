<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repointBagSizeForeignKey('export_order_packing_sub_items', 'bag_packings');
        $this->repointBagSizeForeignKey('export_delivery_order_packing_sub_items', 'bag_packings');
    }

    public function down(): void
    {
        $this->repointBagSizeForeignKey('export_order_packing_sub_items', 'sizes');
        $this->repointBagSizeForeignKey('export_delivery_order_packing_sub_items', 'sizes');
    }

    private function repointBagSizeForeignKey(string $table, string $targetTable): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'bag_size_id')) {
            return;
        }

        $foreignKey = DB::selectOne(
            "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = 'bag_size_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$table]
        );

        if ($foreignKey && $foreignKey->REFERENCED_TABLE_NAME === $targetTable) {
            return;
        }

        if ($foreignKey) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $foreignKey->CONSTRAINT_NAME
            ));
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s_bag_size_id_foreign` FOREIGN KEY (`bag_size_id`) REFERENCES `%s`(`id`) ON DELETE SET NULL',
            $table,
            $table,
            $targetTable
        ));
    }
};
