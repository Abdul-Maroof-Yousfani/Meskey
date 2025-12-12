<?php

namespace Database\Seeders;

use App\Models\Master\Country;
use Illuminate\Database\Seeder;

class NewCountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('seeders/countries.csv'); // CSV file path

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

                    $phoneCode = $rowData['phone_code'] ?? null;

                    Country::updateOrCreate(
                        ['alpha_2_code' => $rowData['iso2']], // unique key
                        [
                            'name' => $rowData['name'],
                            'alpha_3_code' => $rowData['iso3'],
                            'phone_code' => $phoneCode,
                        ]
                    );
                }
            }
            fclose($handle);
        }

        $this->command->info('Countries seeded successfully from CSV.');
    }
}
