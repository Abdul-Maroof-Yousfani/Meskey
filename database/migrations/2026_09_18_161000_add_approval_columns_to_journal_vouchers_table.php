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
        Schema::table('journal_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_vouchers', 'am_approval_status')) {
                $table->string('am_approval_status')->default('pending')->after('jv_status');
            }
            if (!Schema::hasColumn('journal_vouchers', 'am_change_made')) {
                $table->string('am_change_made')->default('1')->after('am_approval_status');
            }
            if (!Schema::hasColumn('journal_vouchers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('am_change_made');
            }
        });

        // Backfill existing records: all existing JVs are already approved
        DB::table('journal_vouchers')->update([
            'am_approval_status' => 'approved',
            'am_change_made' => '1',
        ]);

        // If created_by is null, set to approve_user_id or 1
        DB::statement("UPDATE journal_vouchers SET created_by = COALESCE(approve_user_id, 1) WHERE created_by IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('journal_vouchers', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('journal_vouchers', 'am_change_made')) {
                $table->dropColumn('am_change_made');
            }
            if (Schema::hasColumn('journal_vouchers', 'am_approval_status')) {
                $table->dropColumn('am_approval_status');
            }
        });
    }
};
