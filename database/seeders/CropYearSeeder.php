<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Master\CropYear;
use App\Models\Company;

class CropYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all companies
        $companies = \App\Models\Acl\Company::all();

        foreach ($companies as $company) {
            $cropYears = [
                [
                    'name' => '2024-2025',
                    'description' => 'Crop year from 2024 to 2025',
                    'status' => 'active',
                ],
                [
                    'name' => '2025-2026',
                    'description' => 'Crop year from 2025 to 2026',
                    'status' => 'active',
                ],
                [
                    'name' => '2026-2027',
                    'description' => 'Crop year from 2026 to 2027',
                    'status' => 'active',
                ],
            ];

            foreach ($cropYears as $year) {
                CropYear::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $year['name']
                    ],
                    [
                        'description' => $year['description'],
                        'status' => $year['status'],
                    ]
                );
            }
        }
    }
}
