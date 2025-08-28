<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('approval_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('approval_modules')->onDelete('cascade');
            $table->unsignedBigInteger('record_id');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->integer('required_count')->default(1);
            $table->integer('current_count')->default(0);
            $table->integer('approval_cycle')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index(['record_id', 'module_id', 'approval_cycle']);
            $table->unique(['module_id', 'record_id', 'role_id', 'approval_cycle'], 'unique_approval_row');
        });
    }

    public function down()
    {
        Schema::dropIfExists('approval_rows');
    }
};
