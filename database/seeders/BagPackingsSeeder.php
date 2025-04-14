<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BagPackingsSeeder extends Seeder
{
    public function run()
    {
        $bagPackings = [
            ['name' => '1 kg'],
            ['name' => '5 kg'],
            ['name' => '10 kg'],
            ['name' => '15 kg'],
            ['name' => '20 kg'],
            ['name' => '25 kg'],
            ['name' => '35 kg'],
            ['name' => '40 kg'],
            ['name' => '50 kg'],
            ['name' => '60 KG'],
            ['name' => '100 kg'],
            ['name' => 'WHITE PAPER BAG'],
            ['name' => 'JAMBO BAGS'],
            ['name' => 'CARTON'],
        ];

        DB::table('bag_packings')->insert($bagPackings);
    }
}
