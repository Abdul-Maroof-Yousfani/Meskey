<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $query = "
            INSERT INTO `cities` (`id`, `name`, `company_id`) VALUES
            (1, 'Shikarpur', 1),
            (2, 'Bunga Hayat', 1),
            (3, 'Karachi', 1),
            (4, 'Larkana', 1),
            (5, 'Narowal', 1);
        ";

        DB::statement($query);
    }
}
