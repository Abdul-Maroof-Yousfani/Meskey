<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            ArrivalTruckTypeSeeder::class,
            SaudaTypeSeeder::class,
            BagTypesSeeder::class,
            BagPackingsSeeder::class,
            BagConditionsSeeder::class,
            TransactionVoucherTypesSeeder::class,
        ]);
    }
}
