<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TruckSizeRange;

class TruckSizeRangeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['min_number' => 25000, 'max_number' => 34999, 'status' => 'active'],
            ['min_number' => 35000, 'max_number' => 39999, 'status' => 'active'],
            ['min_number' => 40000, 'max_number' => 44999, 'status' => 'inactive'],
            ['min_number' => 45000, 'max_number' => 50000, 'status' => 'inactive'],
        ];

        foreach ($data as $item) {
            TruckSizeRange::create($item);
        }
    }
}
