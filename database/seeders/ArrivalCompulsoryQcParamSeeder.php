<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Master\ArrivalCompulsoryQcParam;
use Illuminate\Support\Facades\DB;

class ArrivalCompulsoryQcParamSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('arrival_compulsory_qc_params')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $params = [
            ['name' => 'Yeild (%)', 'type' => 'text', 'options' => null, 'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Overall Look', 'type' => 'dropdown', 'options' => json_encode(['Bad Look', 'Normal Look', 'Creamish Look']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Bad Smell', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Heat Up', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Fungus', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Live Insects', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Web', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Dyed Color', 'type' => 'dropdown', 'options' => json_encode(['Yes', 'No']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'Cooking Type', 'type' => 'dropdown', 'options' => json_encode(['Good', 'Bad']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'QC Advice', 'type' => 'dropdown', 'options' => json_encode(['Accept', 'Reject', 'Recheck']),  'properties' => ['is_protected_for_inner_req' => false], 'calculation_base_type' => 3],
            ['name' => 'QC Remarks', 'type' => 'text', 'options' => null, 'properties' => ['is_protected_for_inner_req' => true], 'calculation_base_type' => 3],
            ['name' => 'Unloading Instructions', 'type' => 'text', 'options' => null, 'properties' => ['is_protected_for_inner_req' => true], 'calculation_base_type' => 3],
        ];

        foreach ($params as $param) {
            ArrivalCompulsoryQcParam::create($param);
        }
    }
}
