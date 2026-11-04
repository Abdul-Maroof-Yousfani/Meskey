<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tables that get the contract status / cancel / continue columns
    private array $cancelTables = ['export_orders', 'delivery_order'];

    private array $cancelColumns = [
        'contract_status',
        'cancel',
        'cancel_reason',
        'cancel_by',
        'cancel_at',
        'continue_reason',
        'continue_by',
        'continue_at',
    ];

    public function up(): void
    {
        foreach ($this->cancelTables as $tableName) {
            $this->addCancelColumns($tableName);
        }

        // One filing per save, against one export order
        // InnoDB is set explicitly: the server default is MyISAM, which silently ignores foreign keys
        if (!Schema::hasTable('export_filings')) {
            Schema::create('export_filings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('export_order_id');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Restrict: an export order cannot be hard-deleted while it has filings
                $table->foreign('export_order_id')
                    ->references('id')->on('export_orders')
                    ->restrictOnDelete();
            });
        }

        // The documents uploaded inside a filing
        if (!Schema::hasTable('export_filing_documents')) {
            Schema::create('export_filing_documents', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('export_filing_id');
                $table->string('document_name');
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->string('file_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();

                $table->foreign('export_filing_id')
                    ->references('id')->on('export_filings')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('export_filing_documents');
        Schema::dropIfExists('export_filings');

        foreach ($this->cancelTables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach ($this->cancelColumns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function addCancelColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'contract_status')) {
                $table->string('contract_status', 100)->nullable()->default('continue')->after('am_approval_status');
            }
            if (!Schema::hasColumn($tableName, 'cancel')) {
                $table->boolean('cancel')->default(0)->after('contract_status');
            }
            if (!Schema::hasColumn($tableName, 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancel');
            }
            if (!Schema::hasColumn($tableName, 'cancel_by')) {
                $table->unsignedBigInteger('cancel_by')->nullable()->after('cancel_reason');
            }
            if (!Schema::hasColumn($tableName, 'cancel_at')) {
                $table->timestamp('cancel_at')->nullable()->after('cancel_by');
            }
            if (!Schema::hasColumn($tableName, 'continue_reason')) {
                $table->text('continue_reason')->nullable()->after('cancel_at');
            }
            if (!Schema::hasColumn($tableName, 'continue_by')) {
                $table->unsignedBigInteger('continue_by')->nullable()->after('continue_reason');
            }
            if (!Schema::hasColumn($tableName, 'continue_at')) {
                $table->timestamp('continue_at')->nullable()->after('continue_by');
            }
        });
    }
};
