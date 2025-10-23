<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->string('payment_to')->nullable()->after('id');
            $table->string('payment_to_type')->nullable()->after('payment_to');
            $table->foreign('account_id')
                  ->references('id')
                  ->on('accounts');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('payment_to')->nullable()->after('id');
            $table->string('payment_to_type')->nullable()->after('payment_to');
            $table->foreign('account_id')
                  ->references('id')
                  ->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn(['payment_to', 'payment_to_type']);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_to', 'payment_to_type']);
        });
    }
};
