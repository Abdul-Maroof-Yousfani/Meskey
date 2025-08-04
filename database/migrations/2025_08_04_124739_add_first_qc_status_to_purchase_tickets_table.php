<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_tickets', function (Blueprint $table) {
            $table->enum('first_qc_status', ['resampling', 'pending', 'rejected', 'approved'])->nullable()->after('qc_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_tickets', function (Blueprint $table) {
            $table->dropColumn('first_qc_status');
        });
    }
};
