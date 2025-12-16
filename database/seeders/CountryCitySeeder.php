<?php

namespace Database\Seeders;

use App\Models\Master\CountryCity;
use Illuminate\Database\Seeder;

class CountryCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('seeders/cities.csv'); // CSV file path

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $this->command->error("CSV file not found or not readable at: $filePath");

            return;
        }

        $header = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (! $header) {
                    $header = array_map('trim', $row); // trim header
                } else {
                    $rowData = array_combine($header, $row);
                    $rowData = array_map('trim', $rowData);

                    // Validate required fields
                    if (empty($rowData['name']) || empty($rowData['country_id']) || empty($rowData['country_code'])) {
                        $this->command->warn('Skipping row, missing required fields: '.json_encode($rowData));

                        continue;
                    }

                    // Seed city
                    CountryCity::updateOrCreate(
                        [
                            'name' => $rowData['name'],
                            'country_id' => $rowData['country_id'],
                        ],
                        [
                            'country_code' => $rowData['country_code'],
                        ]
                    );
                }
            }
            fclose($handle);
        }

        $this->command->info('Cities seeded successfully from CSV.');
    }
}
