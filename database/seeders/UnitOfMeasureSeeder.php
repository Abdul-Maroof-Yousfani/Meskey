<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitOfMeasureSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('unit_of_measures')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Comprehensive data for units of measure
        $data = [
            ['company_id' => 1, 'name' => 'Kilogram', 'description' => 'Used for measuring weight in kilograms.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Liter', 'description' => 'Used for measuring volume in liters.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Meter', 'description' => 'Used for measuring length in meters.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Piece', 'description' => 'Used for counting individual items.', 'status' => 'inactive'],
            ['company_id' => 1, 'name' => 'Gram', 'description' => 'Used for measuring smaller weights.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Milliliter', 'description' => 'Used for measuring smaller volumes.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Square Meter', 'description' => 'Used for measuring area in square meters.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Cubic Meter', 'description' => 'Used for measuring large volumes.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Ton', 'description' => 'Used for measuring heavy weights.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Pack', 'description' => 'Used for measuring items in packs.', 'status' => 'inactive'],
            ['company_id' => 1, 'name' => 'Dozen', 'description' => 'Used for counting items in groups of 12.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Carton', 'description' => 'Used for packaging and transportation.', 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Bottle', 'description' => 'Used for liquid storage units.', 'status' => 'inactive'],
        ];

        // Insert data in one batch
        DB::table('unit_of_measures')->insert($data);
    }
}
