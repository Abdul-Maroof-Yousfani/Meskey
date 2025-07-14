<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('brokers', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('owner_name')->nullable()->after('company_name');
            $table->string('owner_mobile_no')->nullable()->after('owner_name');
            $table->string('owner_cnic_no')->nullable()->after('owner_mobile_no');
            $table->string('next_to_kin')->nullable()->after('owner_cnic_no');
            $table->string('next_to_kin_mobile_no')->nullable()->after('next_to_kin');
            $table->text('owner_bank_detail')->nullable()->after('next_to_kin_mobile_no');
            $table->text('company_bank_detail')->nullable()->after('owner_bank_detail');
            $table->string('attachment')->nullable()->after('stn');
            $table->json('company_location_ids')->nullable()->after('attachment');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('company_location_ids');
        });
    }

    public function down()
    {
        Schema::table('brokers', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'owner_name',
                'owner_mobile_no',
                'owner_cnic_no',
                'next_to_kin',
                'next_to_kin_mobile_no',
                'owner_bank_detail',
                'company_bank_detail',
                'attachment',
                'company_location_ids',
                'account_id',
            ]);
        });
    }
};
