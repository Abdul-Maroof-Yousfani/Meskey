<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
            $table->string('customer_bank_type')->nullable()->after('other_specifications');
            $table->unsignedBigInteger('customer_bank_id')->nullable()->after('customer_bank_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->dropColumn(['customer_bank_type', 'customer_bank_id']);
        });
    }
};
