<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanyLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('company_locations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $locations = [
            ['name' => 'Karachi', 'code' => 'KHI'],
            ['name' => 'Shikarpur', 'code' => 'SKP'],
            ['name' => 'Larkana', 'code' => 'LRK'],
            ['name' => 'Bunga Hayat', 'code' => 'BGH'],
            ['name' => 'Narowal', 'code' => 'NRW'],
        ];

        foreach ($locations as $location) {
            DB::table('company_locations')->insert([
                'company_id' => 1, // Change this as needed
                'name' => $location['name'],
                'code' => $location['code'],
                'is_protected' => 1,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
