<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Enum modify karne ke liye raw query use karna parta hai
        DB::statement("
            ALTER TABLE payment_requests
            MODIFY request_type ENUM('payment', 'freight_payment', 'freight_labour_payment', 'freight_advance_payment')
        ");
    }

    public function down()
    {
        // Rollback previous enum
        DB::statement("
            ALTER TABLE payment_requests
            MODIFY request_type ENUM('payment', 'freight_payment')
        ");
    }
};
