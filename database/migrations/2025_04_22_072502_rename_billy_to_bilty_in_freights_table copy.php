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
        Schema::table('freights', function (Blueprint $table) {
            $table->renameColumn('freight_written_on_billy', 'freight_written_on_bilty');
            $table->renameColumn('billy_document', 'bilty_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freights', function (Blueprint $table) {
            $table->renameColumn('freight_written_on_bilty', 'freight_written_on_billy');
            $table->renameColumn('bilty_document', 'billy_document');
        });
    }
};
