<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequestApprovalRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_request_id' => 'required|exists:payment_requests,id',
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string|max:1000',
            'payment_request_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    // dd($this->status, $value);
                    if ($this->status == 'approved' && $value !== null) {
                        $this->validateMaximumAmount($attribute, $value, $fail);
                    }
                }
            ],
            'freight_pay_request_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    // dd($this->status, $value);
                    if ($this->status == 'approved' && $value !== null) {
                        $this->validateMaximumAmountFreight($attribute, $value, $fail);
                    }
                }
            ],
            'total_amount' => 'nullable|numeric',
            'bag_weight' => 'nullable|numeric|min:0',
            'bag_weight_amount' => 'nullable|numeric',
            'loading_weighbridge_amount' => 'nullable|numeric',
            'bag_rate_amount' => 'nullable|numeric',
            'sampling_results' => 'nullable|array',
            'sampling_results.*.slab_type_id' => 'nullable|integer',
            'sampling_results.*.slab_name' => 'nullable|string',
            'sampling_results.*.checklist_value' => 'nullable|numeric',
            'sampling_results.*.suggested_deduction' => 'nullable|numeric',
            'sampling_results.*.applied_deduction' => 'nullable|numeric',
            'sampling_results.*.deduction_amount' => 'nullable|numeric',
            'compulsory_results' => 'nullable|array',
            'compulsory_results.*.qc_param_id' => 'nullable|integer',
            'compulsory_results.*.qc_name' => 'nullable|string',
            'compulsory_results.*.applied_deduction' => 'nullable|numeric',
            'compulsory_results.*.deduction_amount' => 'nullable|numeric',
            'other_deduction' => 'nullable|array',
            'other_deduction.kg_value' => 'nullable|numeric|min:0',
            'other_deduction.kg_amount' => 'nullable|numeric',
            'other_deduction.deduction_amount' => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'payment_request_id.required' => 'Payment request ID is required.',
            'payment_request_id.exists' => 'Invalid payment request.',
            'status.required' => 'Approval status is required.',
            'status.in' => 'Status must be either approved or rejected.',
            'payment_request_amount.min' => 'Payment request amount must be greater than or equal to 0.',
            'freight_pay_request_amount.min' => 'Freight payment amount must be greater than or equal to 0.',
            'bag_weight.min' => 'Bag weight must be greater than or equal to 0.',
        ];
    }


    protected function validateMaximumAmount($attribute, $value, $fail)
    {
        $paymentRequest = \App\Models\Procurement\PaymentRequest::find($this->payment_request_id);
        // dd($paymentRequest);
        if (!$paymentRequest) {
            $fail('Invalid payment request.');
            return;
        }

        $paymentRequestData = $paymentRequest->paymentRequestData;
        $purchaseOrder = $paymentRequestData->purchaseOrder;

        $totalApprovedPayments = \App\Models\Procurement\PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'payment')
            ->where('status', 'approved')
            ->where('id', '!=', $paymentRequest->id)
            ->sum('amount');

        $totalAmountAfterApproval = $totalApprovedPayments + $value;

        // dd($totalAmountAfterApproval);
        $maxAllowedAmount = $this->total_amount ?? $paymentRequestData->total_amount;

        // dd(bccomp((string)$totalAmountAfterApproval, (string)$maxAllowedAmount, 2), $totalAmountAfterApproval, (float)$maxAllowedAmount, 1.9 == 1.9);
        if (bccomp((string)$totalAmountAfterApproval, (string)$maxAllowedAmount, 2) === 1) {
            $remainingAmount = $maxAllowedAmount - $totalApprovedPayments;

            if (bccomp((string)$remainingAmount, '0.00', 2) === 0) {
                $fail("No further payment requests can be approved for this contract as the full allowed amount has already been approved.");
            } else {
                $fail("Maximum amount exceeded. You can only approve up to " . number_format($remainingAmount, 2));
            }
        }
    }

    protected function validateMaximumAmountFreight($attribute, $value, $fail)
    {
        $paymentRequest = \App\Models\Procurement\PaymentRequest::find($this->payment_request_id);
        // dd($paymentRequest);
        if (!$paymentRequest) {
            $fail('Invalid payment request.');
            return;
        }

        $paymentRequestData = $paymentRequest->paymentRequestData;
        $purchaseOrder = $paymentRequestData->purchaseOrder;

        $totalApprovedPayments = \App\Models\Procurement\PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'freight_payment')
            ->where('status', 'approved')
            ->where('id', '!=', $paymentRequest->id)
            ->sum('amount');

        $totalAmountAfterApproval = $totalApprovedPayments + $value;

        // dd($totalAmountAfterApproval);
        $maxAllowedAmount = $this->total_amount ?? $paymentRequestData->total_amount;

        // dd(bccomp((string)$totalAmountAfterApproval, (string)$maxAllowedAmount, 2), $totalAmountAfterApproval, (float)$maxAllowedAmount, 1.9 == 1.9);
        if (bccomp((string)$totalAmountAfterApproval, (string)$maxAllowedAmount, 2) === 1) {
            $remainingAmount = $maxAllowedAmount - $totalApprovedPayments;

            if (bccomp((string)$remainingAmount, '0.00', 2) === 0) {
                $fail("No further freight requests can be approved for this contract as the full allowed amount has already been approved.");
            } else {
                $fail("Maximum amount exceeded. You can only approve up to " . number_format($remainingAmount, 2));
            }
        }
    }
}
