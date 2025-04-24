<?php

namespace Database\Seeders;

use App\Models\Master\Account\TransactionVoucherType;
use Illuminate\Database\Seeder;

class TransactionVoucherTypesSeeder extends Seeder
{
    public function run()
    {
        $voucherTypes = [
            [
                'name' => 'Received Voucher',
                'code' => 'RV',
                'description' => 'For recording received payments',
                'status' => 'active'
            ],
            [
                'name' => 'Payment Voucher',
                'code' => 'PV',
                'description' => 'For recording payments made',
                'status' => 'active'
            ],
            [
                'name' => 'Purchase Invoice',
                'code' => 'PI',
                'description' => 'For recording regular purchases',
                'status' => 'active'
            ],
            [
                'name' => 'Sale Invoice',
                'code' => 'SI',
                'description' => 'For recording sales',
                'status' => 'active'
            ],
            [
                'name' => 'Journal Voucher',
                'code' => 'JV',
                'description' => 'For manual journal entries',
                'status' => 'active'
            ],
            [
                'name' => 'Purchase Invoice (Raw Material)',
                'code' => 'PIRM',
                'description' => 'For recording raw material purchases',
                'status' => 'active'
            ]
        ];

        foreach ($voucherTypes as $type) {
            TransactionVoucherType::create($type);
        }
    }
}
