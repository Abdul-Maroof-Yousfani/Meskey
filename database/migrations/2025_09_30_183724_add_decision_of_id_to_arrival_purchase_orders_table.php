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
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('decision_of_id')->nullable()->after('contract_date');
            // agar foreign key relation banana ho to uncomment karein
             $table->foreign('decision_of_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            // pehle foreign key hatao agar banai ho
            $table->dropForeign(['decision_of_id']);
            $table->dropColumn('decision_of_id');
        });
    }
};
