<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Acl\{Company};
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('companies')->truncate();
        DB::table('company_user_role')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $faker = Faker::create();

        $user = User::create([
            'name' => 'Naveed Ullah',
            'user_type' => 'super-admin',
            'email' => 'naveed.ullah@innovative-net.com',
            'password' => bcrypt('12345678'),
        ]);


        $company = Company::create([
            'name' => 'Meskay & Femtee',
            'email' => 'admin@meskayfemtee.net',
            'phone' => '+1895433-8308',
            'address' => '635 South 10th Street, Allentown PA 18103 USA',
        ]);

        $role = Role::create(['name' => 'Admin']);


 $user->companies()->attach($company->id, ['role_id' => $role->id]);

        $permissions = Permission::pluck('id', 'id')->all();
        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);
    }
}
