<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->integer('penalty_adjust_to')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->decimal('penalty_adjust_to', 15, 2)->nullable()->change();
        });
    }
};

