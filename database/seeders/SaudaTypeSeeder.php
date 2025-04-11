<?php

namespace Database\Seeders;

use App\Models\SaudaType;
use Illuminate\Database\Seeder;

class SaudaTypeSeeder extends Seeder
{
    public function run()
    {
        $saudaTypes = [
            [
                'name' => 'Pohanch',
                'is_protected' => 1,
            ],
            [
                'name' => 'Thadda',
                'is_protected' => 1,
            ]
        ];

        foreach ($saudaTypes as $type) {
            SaudaType::create($type);
        }
    }
}
