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
        Schema::create('labour_rate', function (Blueprint $table) {
            $table->id();
            $table->float("rate");
            $table->foreignId("bag_packing_id")->constrained("bag_packings");
            $table->foreignId("category_id")->constrained("categories");
            $table->foreignId("factory_id")->constrained("arrival_locations");
            $table->foreignId("company_id")->constrained("companies");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->string("description")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labour_rate');
    }
};
