<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if master permission exists to get parent_id
        $master = DB::table('permissions')->where('name', 'master')->first();
        $parentId = $master ? $master->id : null;

        $permissions = [
            ['name' => 'transporter', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['name' => 'transporter-list', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['name' => 'transporter-create', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['name' => 'transporter-edit', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['name' => 'transporter-delete', 'guard_name' => 'web', 'parent_id' => $parentId],
        ];

        foreach ($permissions as $permission) {
            if (!DB::table('permissions')->where('name', $permission['name'])->exists()) {
                DB::table('permissions')->insert($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->where('name', 'like', 'transporter%')->delete();
    }
};
