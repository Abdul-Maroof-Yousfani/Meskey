<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSymbolOfQcToProductSlabTypesTable extends Migration
{
    public function up()
    {
        Schema::table('product_slab_types', function (Blueprint $table) {
            $table->string('qc_symbol')->default('%')->after('description');
        });
    }

    public function down()
    {
        Schema::table('product_slab_types', function (Blueprint $table) {
            $table->dropColumn('qc_symbol');
        });
    }
}
