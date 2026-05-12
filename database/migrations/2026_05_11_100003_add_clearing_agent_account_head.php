<?php

use App\Models\Master\Account\Account;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Account::where('hierarchy_path', '2-6')->exists()) {
            return;
        }

        $liabilities = Account::where('hierarchy_path', '2')->first();
        if (! $liabilities) {
            return;
        }

        $lastAccount = Account::orderByDesc('id')->first();
        $lastNumber = $lastAccount ? (int) preg_replace('/\D/', '', (string) $lastAccount->unique_no) : 0;
        $nextNumber = str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);

        DB::table('accounts')->insert([
            'company_id' => $liabilities->company_id,
            'unique_no' => 'ACC-'.$nextNumber,
            'name' => 'Clearing Agent',
            'table_name' => '',
            'request_account_id' => 0,
            'account_type' => 'credit',
            'parent_id' => $liabilities->id,
            'parent_unique_no' => $liabilities->unique_no,
            'is_operational' => 'no',
            'hierarchy_path' => '2-6',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('accounts')->where('hierarchy_path', '2-6')->where('name', 'Clearing Agent')->delete();
    }
};
