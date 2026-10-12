<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('pre_sale_inspection_items', 'is_confirmed')) {
            Schema::table('pre_sale_inspection_items', function (Blueprint $table) {
                $table->boolean('is_confirmed')->default(0)->after('weight');
            });
        }

        $perm = Permission::firstOrCreate([
            'name' => 'dekh-confirmation-approval',
            'guard_name' => 'web',
        ], [
            'parent_id' => 202,
            'description' => 'Permission to approve Dekh Confirmation records',
        ]);

        if ($perm && $perm->parent_id !== 202) {
            $perm->update(['parent_id' => 202]);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole && !$adminRole->hasPermissionTo('dekh-confirmation-approval')) {
            $adminRole->givePermissionTo('dekh-confirmation-approval');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pre_sale_inspection_items', 'is_confirmed')) {
            Schema::table('pre_sale_inspection_items', function (Blueprint $table) {
                $table->dropColumn('is_confirmed');
            });
        }

        $perm = Permission::where('name', 'dekh-confirmation-approval')->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
