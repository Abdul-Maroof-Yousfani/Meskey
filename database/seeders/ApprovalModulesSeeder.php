<?php

namespace Database\Seeders;

use App\Models\ApprovalsModule\ApprovalModule;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ApprovalModulesSeeder extends Seeder
{
    public function run()
    {
        $modules = [
            [
                'name' => 'Bank Payment Voucher',
                'slug' => 'bank_payment_voucher',
                'model_class' => 'App\\Models\\PaymentVoucher',
                'requires_sequential_approval' => true,
                'roles' => [
                    ['role' => 'finance', 'count' => 3, 'order' => 0],
                    ['role' => 'audit', 'count' => 2, 'order' => 1],
                    ['role' => 'finance_manager', 'count' => 1, 'order' => 2],
                ]
            ],
            [
                'name' => 'Cash Payment Voucher',
                'slug' => 'cash_payment_voucher',
                'model_class' => 'App\\Models\\PaymentVoucher',
                'requires_sequential_approval' => false,
                'roles' => [
                    ['role' => 'audit', 'count' => 2, 'order' => 0],
                    ['role' => 'finance_manager', 'count' => 1, 'order' => 1],
                ]
            ],
        ];

        foreach ($modules as $moduleData) {
            $module = ApprovalModule::create([
                'name' => $moduleData['name'],
                'slug' => $moduleData['slug'],
                'model_class' => $moduleData['model_class'],
                'requires_sequential_approval' => $moduleData['requires_sequential_approval'],
            ]);

            foreach ($moduleData['roles'] as $roleData) {
                $role = Role::where('name', $roleData['role'])->first();
                if ($role) {
                    $module->roles()->create([
                        'role_id' => $role->id,
                        'approval_count' => $roleData['count'],
                        'approval_order' => $roleData['order'],
                    ]);
                }
            }
        }
    }
}
