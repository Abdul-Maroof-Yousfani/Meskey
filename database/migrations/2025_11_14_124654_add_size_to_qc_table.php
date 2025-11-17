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
        Schema::table('qc', function (Blueprint $table) {
            $table->string("size")->nullable();
            $table->string("bio")->nullable();
            $table->string("smell")->nullable();
            $table->string("printing")->nullable();
            $table->string("bottom_stitching")->nullable();
            $table->string("ready_to_pack")->nullable();
            $table->date("date")->nullable();
            $table->string("remarks")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc', function (Blueprint $table) {
            //
        });
    }
};
