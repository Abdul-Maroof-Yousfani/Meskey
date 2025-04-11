<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('sauda_type_id')->nullable()->after('broker_id');

            $table->foreign('sauda_type_id')
                ->references('id')
                ->on('sauda_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['sauda_type_id']);

            $table->dropColumn('sauda_type_id');
        });
    }
};
