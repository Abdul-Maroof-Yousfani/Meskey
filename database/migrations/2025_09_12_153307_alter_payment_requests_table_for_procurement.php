<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('request_no')->nullable()->after('id');
            $table->foreignId('supplier_id')->nullable()->constrained()->after('payment_request_data_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('supplier_id');
            $table->foreignId('grn_id')->nullable()->constrained('good_receive_notes')->after('purchase_order_id');
            $table->foreignId('requested_by')->nullable()->constrained('users')->after('grn_id');
            $table->timestamp('request_date')->nullable()->after('requested_by');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('request_date');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('description')->nullable()->after('amount');
            $table->enum('payment_type', ['advance', 'against_receiving'])->nullable()->after('description');
        });

        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->after('purchase_order_id');
            $table->foreignId('grn_id')->nullable()->constrained('good_receive_notes')->after('supplier_id');
            $table->unsignedBigInteger('store_purchase_order_id')->nullable();

            $table->text('description')->nullable()->after('notes');
            $table->enum('payment_type', ['advance', 'against_receiving'])->nullable()->after('description');

            $table->string('supplier_name')->nullable()->change();
            $table->decimal('contract_rate', 15, 2)->nullable()->change();

            $table->foreign('store_purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            // $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['grn_id']);
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['approved_by']);

            $table->dropColumn([
                'request_no',
                'supplier_id',
                'purchase_order_id',
                'grn_id',
                'requested_by',
                'request_date',
                'approved_by',
                'approved_at',
                'description',
                'payment_type'
            ]);
        });

        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['grn_id']);
            $table->dropForeign(['store_purchase_order_id']);

            $table->dropColumn([
                'supplier_id',
                'grn_id',
                'store_purchase_order_id',
                'description',
                'payment_type'
            ]);

            $table->string('supplier_name')->nullable(false)->change();
            $table->decimal('contract_rate', 15, 2)->nullable(false)->change();
        });
    }
};
