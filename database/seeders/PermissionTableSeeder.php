<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use DB;
class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
       DB::table('permissions')->insert([
    ['id' => 1, 'parent_id' => null, 'name' => 'Access Control', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 2, 'parent_id' => 1, 'name' => 'role', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 3, 'parent_id' => 2, 'name' => 'role-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 4, 'parent_id' => 2, 'name' => 'role-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 5, 'parent_id' => 2, 'name' => 'role-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 6, 'parent_id' => 2, 'name' => 'role-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 7, 'parent_id' => 1, 'name' => 'user', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 8, 'parent_id' => 7, 'name' => 'user-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 9, 'parent_id' => 7, 'name' => 'user-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 10, 'parent_id' => 7, 'name' => 'user-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 11, 'parent_id' => 7, 'name' => 'user-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 12, 'parent_id' => 7, 'name' => 'user-show', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 13, 'parent_id' => null, 'name' => 'dashboard', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],

    // Adding company and menu under Access Control
    ['id' => 14, 'parent_id' => 1, 'name' => 'company', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 15, 'parent_id' => 14, 'name' => 'company-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 16, 'parent_id' => 14, 'name' => 'company-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 17, 'parent_id' => 14, 'name' => 'company-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 18, 'parent_id' => 14, 'name' => 'company-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],

    ['id' => 19, 'parent_id' => 1, 'name' => 'menu', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 20, 'parent_id' => 19, 'name' => 'menu-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 21, 'parent_id' => 19, 'name' => 'menu-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 22, 'parent_id' => 19, 'name' => 'menu-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 23, 'parent_id' => 19, 'name' => 'menu-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],

    // Creating master parent with category, uom, and product
    ['id' => 24, 'parent_id' => null, 'name' => 'master', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 25, 'parent_id' => 24, 'name' => 'category', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 26, 'parent_id' => 25, 'name' => 'category-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 27, 'parent_id' => 25, 'name' => 'category-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 28, 'parent_id' => 25, 'name' => 'category-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 29, 'parent_id' => 25, 'name' => 'category-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],

    ['id' => 30, 'parent_id' => 24, 'name' => 'uom', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 31, 'parent_id' => 30, 'name' => 'uom-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 32, 'parent_id' => 30, 'name' => 'uom-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 33, 'parent_id' => 30, 'name' => 'uom-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 34, 'parent_id' => 30, 'name' => 'uom-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],

    ['id' => 35, 'parent_id' => 24, 'name' => 'product', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 36, 'parent_id' => 35, 'name' => 'product-list', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 37, 'parent_id' => 35, 'name' => 'product-create', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 38, 'parent_id' => 35, 'name' => 'product-edit', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
    ['id' => 39, 'parent_id' => 35, 'name' => 'product-delete', 'guard_name' => 'web', 'created_at' => '2024-04-03 11:09:09', 'updated_at' => '2024-04-03 11:09:09'],
]);
    }
}
