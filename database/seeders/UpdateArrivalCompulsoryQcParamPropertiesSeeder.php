<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Master\ArrivalCompulsoryQcParam;

class UpdateArrivalCompulsoryQcParamPropertiesSeeder extends Seeder
{
    public function run()
    {
        $paramsToUpdate = [
            'QC Remarks',
            'Unloading Instructions'
        ];

        foreach ($paramsToUpdate as $paramName) {
            ArrivalCompulsoryQcParam::where('name', $paramName)
                ->update([
                    'properties' => json_encode([
                        'is_protected_for_inner_req' => true,
                    ])
                ]);
        }

        ArrivalCompulsoryQcParam::whereNotIn('name', $paramsToUpdate)
            ->update([
                'properties' => json_encode([
                    'is_protected_for_inner_req' => false,
                ])
            ]);
    }
}
