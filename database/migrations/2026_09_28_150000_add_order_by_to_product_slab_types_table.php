<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_slab_types', function (Blueprint $table) {
            $table->integer('order_by')->nullable()->default(null)->after('for_general_item');
        });

        // Initialize sequential order for general item slab types
        $generalItems = DB::table('product_slab_types')
            ->where('for_general_item', 1)
            ->orderBy('id', 'asc')
            ->get();

        $order = 1;
        foreach ($generalItems as $item) {
            DB::table('product_slab_types')
                ->where('id', $item->id)
                ->update(['order_by' => $order++]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_slab_types', function (Blueprint $table) {
            $table->dropColumn('order_by');
        });
    }
};
