<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->decimal('production_labour_charges_per_kg', 10, 2)->default(0)->after('description');
        });
    }

    public function down()
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn('production_labour_charges_per_kg');
        });
    }
};
