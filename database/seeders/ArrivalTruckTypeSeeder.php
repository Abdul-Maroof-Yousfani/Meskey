<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Master\ArrivalTruckType;

class ArrivalTruckTypeSeeder extends Seeder
{
    public function run(): void
    {
        $truckTypes = [
            ['id' => 8, 'name' => 'KG', 'company_id' => 1],
            ['id' => 10, 'name' => 'Mun', 'company_id' => 1],
            ['id' => 9, 'name' => 'MT', 'company_id' => 1],
            ['id' => 4, 'name' => 'Six Wheeler', 'company_id' => 1],
            ['id' => 6, 'name' => 'Dumper', 'company_id' => 1],
            ['id' => 7, 'name' => 'Ten Wheeler', 'company_id' => 1],
            ['id' => 1, 'name' => '4 Wheeler', 'company_id' => 1],
            ['id' => 5, 'name' => 'Container', 'company_id' => 1],
            ['id' => 2, 'name' => 'High Wall', 'company_id' => 1],
            ['id' => 3, 'name' => 'Traala', 'company_id' => 1],
        ];

        foreach ($truckTypes as $type) {
            ArrivalTruckType::updateOrCreate(['id' => $type['id']], ['name' => $type['name'], 'company_id' => $type['company_id']]);
        }
    }
}
