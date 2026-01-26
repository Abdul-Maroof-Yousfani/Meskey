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
        Schema::create('debit_note_items_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId("debit_note_id")->constrained("debit_notes")->cascadeOnDelete();
            $table->foreignId("grn_id")->constrained("purchase_order_receivings")->cascadeOnDelete();
            $table->foreignId("bill_id")->constrained("purchase_bills")->cascadeOnDelete();
            $table->foreignId("purchase_bill_data_id")->constrained("purchase_bills_data")->cascadeOnDelete();
            $table->integer("grn_qty");
            $table->integer("debit_note_quantity");
            $table->integer("rate");
            $table->integer('amount');
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->enum("am_approval_status", ["pending", "rejected", "reverted"])->default("pending");
            $table->integer("am_change_made");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_note_items_taable');
    }
};
