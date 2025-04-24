<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('account_type', ['debit', 'credit']);
            $table->foreignId('parent_id')->nullable()->constrained('accounts');
            $table->string('parent_unique_no')->nullable();
            $table->enum('is_operational', ['yes', 'no'])->default('yes');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id']);
            $table->index(['unique_no']);
            $table->index(['status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
