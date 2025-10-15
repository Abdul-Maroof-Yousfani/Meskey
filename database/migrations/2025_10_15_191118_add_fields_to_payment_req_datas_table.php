<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->decimal('exempt', 15, 2)->nullable()->after('module_type');
            $table->decimal('freight_rs', 15, 2)->nullable()->after('exempt');
            $table->decimal('freight_per_ton', 15, 2)->nullable()->after('freight_rs');
            $table->decimal('loading_kanta', 15, 2)->nullable()->after('freight_per_ton');
            $table->decimal('arrived_kanta', 15, 2)->nullable()->after('loading_kanta');
            $table->decimal('other_plus_labour', 15, 2)->nullable()->after('arrived_kanta');
            $table->decimal('dehari_plus_extra', 15, 2)->nullable()->after('other_plus_labour');
            $table->decimal('market_comm', 15, 2)->nullable()->after('dehari_plus_extra');
            $table->decimal('over_weight_ded', 15, 2)->nullable()->after('market_comm');
            $table->decimal('godown_penalty', 15, 2)->nullable()->after('over_weight_ded');
            $table->decimal('other_minus_labour', 15, 2)->nullable()->after('godown_penalty');
            $table->decimal('extra_minus_ded', 15, 2)->nullable()->after('other_minus_labour');
            $table->decimal('commission_percent_ded', 15, 2)->nullable()->after('extra_minus_ded');
            $table->decimal('commission_amount', 15, 2)->nullable()->after('commission_percent_ded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn([
                'exempt',
                'freight_rs',
                'freight_per_ton',
                'loading_kanta',
                'arrived_kanta',
                'other_plus_labour',
                'dehari_plus_extra',
                'market_comm',
                'over_weight_ded',
                'godown_penalty',
                'other_minus_labour',
                'extra_minus_ded',
                'commission_percent_ded',
                'commission_amount',
            ]);
        });
    }
};
