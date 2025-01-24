<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->nullable(); // Parent ID (default 0)
            $table->string('name'); 
            $table->softDeletes();
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
}