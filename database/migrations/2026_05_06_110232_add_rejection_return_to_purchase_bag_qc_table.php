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
        Schema::table('purchase_bag_qc', function (Blueprint $table) {
            $table->float("rejection_return")->default(0)->nullable()->after('rejected_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_bag_qc', function (Blueprint $table) {
            $table->dropColumn('rejection_return');
        });
    }
};
