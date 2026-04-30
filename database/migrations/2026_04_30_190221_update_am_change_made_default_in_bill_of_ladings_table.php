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
        // bill_of_ladings
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->boolean('am_change_made')->default(1)->change();
        });

        // commercial_invoices
        Schema::table('commercial_invoices', function (Blueprint $table) {
            $table->boolean('am_change_made')->default(1)->change();
        });

        // packing_lists
        Schema::table('packing_lists', function (Blueprint $table) {
            $table->boolean('am_change_made')->default(1)->change();
        });
    }

    public function down(): void
    {
        // bill_of_ladings
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->boolean('am_change_made')->default(0)->change();
        });

        // commercial_invoices
        Schema::table('commercial_invoices', function (Blueprint $table) {
            $table->boolean('am_change_made')->default(0)->change();
        });

        // packing_lists
        Schema::table('packing_lists', function (Blueprint $table) {
            $table->boolean('am_change_made')->default(0)->change();
        });
    }
};
