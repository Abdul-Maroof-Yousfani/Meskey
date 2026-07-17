<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->decimal('jv_amount', 15, 2)->default(0)->after('advance_amount');
        });

        DB::table('transaction_voucher_types')->insertOrIgnore([
            'id' => 11,
            'name' => 'Journal Voucher',
            'code' => 'JV',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transaction_voucher_types')->where('code', 'JV')->delete();

        Schema::table('delivery_order', function (Blueprint $table) {
            $table->dropColumn('jv_amount');
        });
    }
};
