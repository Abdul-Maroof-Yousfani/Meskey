```php
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
        if (!Schema::hasTable('by_product_rates')) {
            Schema::create('by_product_rates', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('sublocation_id')->nullable()->index();
                $table->unsignedBigInteger('plant_id')->nullable()->index();

                $table->unsignedBigInteger('product_id')->index();

                $table->string('title')->nullable();
                $table->date('from_date')->nullable()->index();
                $table->date('to_date')->nullable()->index();
                $table->text('description')->nullable();

                $table->tinyInteger('status')
                    ->default(1)
                    ->comment('1: Active, 0: Inactive');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign('location_id')
                    ->references('id')
                    ->on('company_locations')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('by_product_rate_items')) {
            Schema::create('by_product_rate_items', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('by_product_rate_id')->index();
                $table->unsignedBigInteger('by_product_id')->index();

                $table->decimal('rate', 12, 4)->default(0.0000);

                $table->timestamps();

                $table->foreign('by_product_rate_id')
                    ->references('id')
                    ->on('by_product_rates')
                    ->cascadeOnDelete();

                $table->foreign('by_product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('by_product_rate_items');
        Schema::dropIfExists('by_product_rates');
    }
};