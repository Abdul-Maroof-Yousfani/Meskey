<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class AdvancePaymentRequestApprovalRequest extends FormRequest
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
                    if ($this->status == 'approved' && $value !== null) {
                        $this->validateMaximumAmount($attribute, $value, $fail);
                    }
                }
            ],
            'total_amount' => 'nullable|numeric',
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

        if (!$paymentRequest) {
            $fail('Invalid payment request.');
            return;
        }

        $paymentRequestData = $paymentRequest->paymentRequestData;
        $purchaseOrder = $paymentRequest->paymentRequestData->purchaseOrder;

        if (!$purchaseOrder) {
            $fail('Invalid purchase order associated with payment request.');
            return;
        }

        $totalApprovedPayments = \App\Models\Procurement\PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id)
                ->where('is_advance_payment', 1);
        })
            ->where('request_type', 'payment')
            ->where('status', 'approved')
            ->where('is_advance_payment', 1)
            ->where('id', '!=', $paymentRequest->id)
            ->sum('amount');

        // $totalAmountAfterApproval = $totalApprovedPayments + $value;

        $value = number_format((float) $value, 2, '.', '');
        $totalAmountAfterApproval = bcadd((string)$totalApprovedPayments, (string)$value, 2);

        $maxAllowedAmount = $this->total_amount ?? $paymentRequestData->total_amount;
        $remainingAmount = $maxAllowedAmount - $totalApprovedPayments;

        // dd($maxAllowedAmount, $totalAmountAfterApproval);
        // $remainingAmount = $maxAllowedAmount - $totalApprovedPayments;
        // dd($totalApprovedPayments, $value, $maxAllowedAmount, bccomp((string)$totalAmountAfterApproval, (string)$maxAllowedAmount, 2), $remainingAmount, bccomp((string)$remainingAmount, '0.00', 2));

        if (bccomp((string)$totalAmountAfterApproval, (string)$maxAllowedAmount, 2) === 1) {
            $remainingAmount = $maxAllowedAmount - $totalApprovedPayments;
            if (bccomp((string)$remainingAmount, '0.00', 2) === 0) {
                $fail("No further payment requests can be approved for this contract as the full allowed amount has already been approved.");
            } else {
                $fail("Maximum amount exceeded. You can only approve up to " . number_format($remainingAmount, 2) . " for this contract.");
            }
        }
    }
}
