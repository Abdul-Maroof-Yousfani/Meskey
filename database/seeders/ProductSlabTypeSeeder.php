<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSlabTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_slab_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // Yahan company_id ko update karein agar zarurat ho.
        $companyId = 1;

        // Common slab types for all products
        $slabTypes = [
            [
                'company_id' => $companyId,
                'name' => 'Broken',
                'description' => 'Broken grains/kernels',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Moisture',
                'description' => 'Moisture level in product',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Chalky',
                'description' => 'Chalky appearance in product',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Damage',
                'description' => 'General damage in product',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('product_slab_types')->insert($slabTypes);
    }
}
