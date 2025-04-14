<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BagConditionsSeeder extends Seeder
{
    public function run()
    {
        $bagConditions = [
            ['name' => 'New', 'status' => 'Active'],
            ['name' => 'Used', 'status' => 'Active'],
            ['name' => 'Damaged', 'status' => 'Active'],
            ['name' => 'Repaired', 'status' => 'Active'],
        ];

        DB::table('bag_conditions')->insert($bagConditions);
    }
}
