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
        Schema::table('production_voucher_machine_times', function (Blueprint $table) {
            $table->dropForeign('pvm_times_pv_id_fk');
        });

        Schema::table('production_voucher_machine_times', function (Blueprint $table) {
            $table->unsignedBigInteger('production_voucher_id')->nullable()->change();
            $table->foreign('production_voucher_id', 'pvm_times_pv_id_fk')
                  ->references('id')->on('production_vouchers')->onDelete('cascade');

            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('machine_plan_setting_id')->nullable()->after('company_id');
            $table->decimal('hours', 8, 2)->default(0)->after('duration_minutes');
            $table->boolean('is_enabled')->default(true)->after('hours');
            $table->text('remarks')->nullable()->after('is_enabled');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('machine_plan_setting_id')->references('id')->on('machine_plan_settings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_voucher_machine_times', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['machine_plan_setting_id']);
            $table->dropColumn(['company_id', 'machine_plan_setting_id', 'hours', 'is_enabled', 'remarks']);
        });
    }
};
