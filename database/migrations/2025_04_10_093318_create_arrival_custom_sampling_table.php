<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('arrival_custom_sampling', function (Blueprint $table) {
            $table->id();
            $table->string('party_ref_no')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('arrival_custom_sampling')->insert([
            'party_ref_no' => 'N/A',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('arrival_custom_sampling');
    }
};
