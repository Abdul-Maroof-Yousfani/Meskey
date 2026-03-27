<?php

namespace App\Traits;

use App\Models\Procurement\PaymentRequestSamplingResult;

trait HandlesSamplingResults
{
    /**
     * Save sampling results for a payment request.
     *
     * @param  \App\Models\Procurement\PaymentRequestData  $paymentRequestData
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function saveSamplingResults($paymentRequestData, $request)
    {
        if ($request->sampling_results) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['slab_type_id'] ?? null,
                    'name' => $result['slab_name'] ?? '',
                    'checklist_value' => $result['checklist_value'] ?? 0,
                    'suggested_deduction' => $result['suggested_deduction'] ?? 0,
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_type' => ($result['suggested_deduction'] ?? 0) > 0 ? 'amount' : 'percentage',
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                ]);
            }
        }

        // Save compulsory sampling results
        if ($request->compulsory_results) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['qc_param_id'] ?? null,
                    'qc_param_id' => $result['qc_param_id'] ?? null,
                    'name' => $result['qc_name'] ?? '',
                    'checklist_value' => 0,
                    'suggested_deduction' => 0,
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_type' => 'amount',
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                ]);
            }
        }
    }

    /**
     * Update sampling results for a payment request.
     *
     * @param  \App\Models\Procurement\PaymentRequestData  $paymentRequestData
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();
        $this->saveSamplingResults($paymentRequestData, $request);
    }
}
