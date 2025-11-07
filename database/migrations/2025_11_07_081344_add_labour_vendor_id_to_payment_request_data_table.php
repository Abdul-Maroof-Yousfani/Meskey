<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->unsignedBigInteger('labour_vendor_id')->nullable()->after('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_data', function (Blueprint $table) {
            $table->dropColumn('labour_vendor_id');
        });
    }
};
