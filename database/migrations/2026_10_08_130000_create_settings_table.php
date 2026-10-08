<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Acl\Company;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            // Polymorphic owner (nullable => global/app-wide settings)
            $table->nullableMorphs('settable'); // settable_type, settable_id
            $table->string('group', 100)->nullable();   // optional grouping, e.g. "notifications"
            $table->string('key', 150);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string|integer|float|boolean|json|datetime
            $table->timestamps();
            $table->unique(['settable_type', 'settable_id', 'key'], 'settings_owner_key_unique');
            $table->index(['group']);
        });

        // Attach company_id and create stock_check (bool) true for existing companies
        if (Schema::hasTable('companies')) {
            $companies = DB::table('companies')->get();
            $now = now();
            foreach ($companies as $company) {
                DB::table('settings')->insert([
                    'settable_type' => Company::class,
                    'settable_id'   => $company->id,
                    'group'         => 'inventory',
                    'key'           => 'stock_check',
                    'value'         => '1',
                    'type'          => 'boolean',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
