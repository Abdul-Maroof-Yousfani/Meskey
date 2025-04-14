<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BagTypesSeeder extends Seeder
{
    public function run()
    {
        $bagTypes = [
            ['name' => 'P.P'],
            ['name' => 'Jute'],
            ['name' => 'Cotton'],
            ['name' => 'Laminated'],
            ['name' => 'Gazetted'],
            ['name' => 'Inner P.P'],
            ['name' => 'Inner Jute'],
            ['name' => 'Polythene'],
            ['name' => 'P Liner'],
            ['name' => 'Jar'],
            ['name' => 'WHITE PAPER BAG'],
            ['name' => 'ONE SIDE BOPP LAMINATED BAG'],
            ['name' => 'JAMBO BAGS'],
            ['name' => 'SINGLE WOVEN POLYPROPLENE'],
            ['name' => 'BULK'],
            ['name' => 'PLASTIC BAG'],
            ['name' => 'CARTON'],
            ['name' => 'LINER BAGS'],
            ['name' => 'NEW SINGLE TRANSPARENT POLYPROPYLENE'],
            ['name' => 'INNER POLYTHENE'],
            ['name' => 'OUTER PP BAGS'],
            ['name' => 'NON WOVEN'],
            ['name' => 'MASTER BAGS'],
            ['name' => 'PE 3 Side Seal'],
            ['name' => 'ONE SIDE LAMINATED'],
            ['name' => 'RIVIANA BRAND'],
        ];

        DB::table('bag_types')->insert($bagTypes);
    }
}
