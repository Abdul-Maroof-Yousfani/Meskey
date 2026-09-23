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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('jv_narration')->nullable()->after('remarks');
            $table->string('reference_no')->nullable()->after('jv_narration')->comment('it is use to store unique numbers like grn_no, sale_order_no and more');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['jv_narration', 'reference_no']);
        });
    }
};
