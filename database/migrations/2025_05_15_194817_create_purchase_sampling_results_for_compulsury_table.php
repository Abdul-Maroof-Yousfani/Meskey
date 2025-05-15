<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_sampling_results_for_compulsury', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('arrival_sampling_request_id');
            $table->unsignedBigInteger('arrival_compulsory_qc_param_id');
            $table->string('compulsory_checklist_value')->nullable();
            $table->string('applied_deduction')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->foreign('company_id', 'pr_asr_company')
                ->references('id')->on('companies')->onDelete('cascade');

            $table->foreign('arrival_sampling_request_id', 'pr_asr_request')
                ->references('id')->on('purchase_sampling_requests')->onDelete('cascade');

            $table->foreign('arrival_compulsory_qc_param_id', 'pr_asr_param')
                ->references('id')->on('arrival_compulsory_qc_params')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_sampling_results_for_compulsury');
    }
};
