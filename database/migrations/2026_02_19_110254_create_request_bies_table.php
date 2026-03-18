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
        Schema::create('request_bies', function (Blueprint $row) {
            $row->id();
            $row->string('name');
            $row->text('description')->nullable();
            $row->enum('status', ['active', 'inactive'])->default('active');
            $row->softDeletes();
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_bies');
    }
};
