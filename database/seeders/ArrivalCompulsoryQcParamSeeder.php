<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Master\ArrivalCompulsoryQcParam;

class ArrivalCompulsoryQcParamSeeder extends Seeder
{
    public function run()
    {
        $params = [
            ['name' => 'Yeild (%)', 'type' => 'text', 'options' => null],
            ['name' => 'Overall Look', 'type' => 'dropdown', 'options' => json_encode(['Bad Look', 'Normal Look', 'Creamish Look'])],
            ['name' => 'Bad Smell', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No'])],
            ['name' => 'Heat Up', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No'])],
            ['name' => 'Fungus', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No'])],
            ['name' => 'Live Insects', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No'])],
            ['name' => 'Web', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No'])],
            ['name' => 'Dyed Color', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No'])],
            ['name' => 'Cooking Type', 'type' => 'dropdown', 'options' => json_encode(['Good', 'Bad'])],
            ['name' => 'QC Advice', 'type' => 'dropdown', 'options' => json_encode(['Accept', 'Reject', 'Recheck'])],
            ['name' => 'QC Remarks', 'type' => 'text', 'options' => null],
            ['name' => 'Unloading Instructions', 'type' => 'text', 'options' => null],
        ];

        foreach ($params as $param) {
            ArrivalCompulsoryQcParam::create($param);
        }
    }
}
