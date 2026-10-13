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
        Schema::table('audit_logs', function (Blueprint $table) {
            // High cardinality index for specific model audit lookup + fast reverse-chronological ordering
            $table->index(['model_id', 'created_at'], 'audit_logs_model_id_created_at_index');
            
            // Compound index for polymorphic model queries with created_at sorting
            $table->index(['model_type', 'model_id', 'created_at'], 'audit_logs_model_type_model_id_created_at_index');

            // Index for action filter
            $table->index('action', 'audit_logs_action_index');

            $table->index('user_id', 'audit_logs_user_id_index');
            
            // Standalone index for created_at sorting/filtering
            $table->index('created_at', 'audit_logs_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_model_id_created_at_index');
            $table->dropIndex('audit_logs_model_type_model_id_created_at_index');
            $table->dropIndex('audit_logs_action_index');
            $table->dropIndex('audit_logs_created_at_index');
        });
    }
};
