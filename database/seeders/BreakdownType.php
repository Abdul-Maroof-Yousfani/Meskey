<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Master\PlantBreakdownType;

class BreakdownType extends Seeder
{
    public function run(): void
    {
        $breakdownTypes = [
                [
                    'name' => 'Loadshedding',
                    'description' => 'Loadshedding',
                    'status' => 'active',
                ],
                [
                    'name' => 'Power Outage',
                    'description' => 'Power Outage',
                    'status' => 'active',
                ],
                [
                    'name' => 'KE Jerk',
                    'description' => 'KE Jerk',
                    'status' => 'active',
                ],
                [
                    'name' => 'Sortex cleaning',
                    'description' => 'Sortex cleaning',
                    'status' => 'active',
                ],
                [
                    'name' => 'Misc',
                    'description' => 'Miscellaneous breakdowns',
                    'status' => 'active',
                ],
            ];

            foreach ($breakdownTypes as $breakdownType) {
                PlantBreakdownType::updateOrCreate(
                    [
                        'company_id' => 1,
                        'name' => $breakdownType['name']
                    ],
                    [
                        'description' => $breakdownType['description'],
                        'status' => $breakdownType['status'],
                    ]
                );
            }
    }
}
