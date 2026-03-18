<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Master\Account\Account;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Account::where('hierarchy_path', '2-5')->exists()) {
            $liabilities = Account::where('hierarchy_path', '2')->first();
            if ($liabilities) {
                Account::create([
                    'company_id' => $liabilities->company_id,
                    'unique_no' => 'ACC-000040', // Hardcoding for simplicity, or I could use generate UniqueNo if I had access to a helper that works here
                    'name' => 'Transporter',
                    'table_name' => '',
                    'request_account_id' => 0,
                    'account_type' => 'credit',
                    'parent_id' => $liabilities->id,
                    'parent_unique_no' => $liabilities->unique_no,
                    'is_operational' => 'no',
                    'hierarchy_path' => '2-5',
                    'status' => 'active',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Account::where('hierarchy_path', '2-5')->where('name', 'Transporter')->delete();
    }
};
