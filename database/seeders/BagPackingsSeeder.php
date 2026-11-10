<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BagPackingsSeeder extends Seeder
{
    public function run()
    {
        $bagPackings = [
            ['name' => '1 kg', 'value' => 1],
            ['name' => '5 kg', 'value' => 5],
            ['name' => '10 kg', 'value' => 10],
            ['name' => '15 kg', 'value' => 15],
            ['name' => '20 kg', 'value' => 20],
            ['name' => '25 kg', 'value' => 25],
            ['name' => '35 kg', 'value' => 35],
            ['name' => '40 kg', 'value' => 40],
            ['name' => '50 kg', 'value' => 50],
            ['name' => '60 KG', 'value' => 60],
            ['name' => '100 kg', 'value' => 100],
            ['name' => 'WHITE PAPER BAG', 'value' => null],
            ['name' => 'JAMBO BAGS', 'value' => null],
            ['name' => 'CARTON', 'value' => null],
        ];

        DB::table('bag_packings')->insert($bagPackings);
    }
}
