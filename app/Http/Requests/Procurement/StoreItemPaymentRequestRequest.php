<?php

namespace App\Http\Requests\Procurement;

use App\Models\Master\Account\GoodReceiveNote;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemPaymentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 'is_advance' => 'required|boolean',
            'purchase_order_id' => 'required_if:is_advance,1|nullable|exists:purchase_orders,id',
            'purchase_order_receiving_id' => 'required_if:is_advance,0|nullable|exists:good_receive_notes,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                $this->validatePaymentAmount()
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    protected function validatePaymentAmount()
    {
        return function ($attribute, $value, $fail) {
            $isAdvance = $this->input('is_advance');
            $purchaseOrderId = $this->input('purchase_order_id');
            $grnId = $this->input('purchase_order_receiving_id');
            $amount = floatval($value);

            if ($isAdvance && $purchaseOrderId) {
                $purchaseOrder = PurchaseOrder::with('items')->find($purchaseOrderId);

                if (!$purchaseOrder) {
                    $fail('Invalid purchase order selected.');
                    return;
                }

                $totalAmount = $purchaseOrder->items->sum('total');
                $paidAmount = PaymentRequest::where('purchase_order_id', $purchaseOrderId)
                    ->where('status', '!=', 'rejected')
                    ->sum('amount');
                $remainingAmount = $totalAmount - $paidAmount;

                if ($amount > $remainingAmount) {
                    $fail("Payment amount cannot exceed the remaining amount of Rs. " . number_format($remainingAmount, 2));
                }
            } elseif (!$isAdvance && $grnId) {
                $grn = PurchaseOrderReceiving::find($grnId);

                if (!$grn) {
                    $fail('Invalid GRN selected.');
                    return;
                }

                $totalAmount = $grn->purchaseOrderReceivingData->sum('total');
                $paidAmount = PaymentRequest::where('purchase_order_receiving_id', $grnId)
                    ->where('status', '!=', 'rejected')
                    ->sum('amount');
                $remainingAmount = $totalAmount - $paidAmount;

                if ($amount > $remainingAmount) {
                    $fail("Payment amount cannot exceed the remaining amount of Rs. " . number_format($remainingAmount, 2));
                }
            }
        };
    }

    public function messages()
    {
        return [
            'purchase_order_id.required_if' => 'Purchase order is required for advance payment.',
            'purchase_order_receiving_id.required_if' => 'GRN is required for payment against receiving.',
            'amount.max_remaining' => 'Payment amount cannot exceed the remaining amount.',
        ];
    }
}
