<?php

namespace App\Http\Requests\Procurement\Store;

use App\Models\Procurement\Store\PurchaseOrderData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderReceivingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data_id' => 'required|exists:purchase_order_data,id',
            'item_id' => 'required|exists:products,id',
            'total_amount' => 'required|numeric|min:0',
            'purchase_order_data_id' => 'required|exists:purchase_order_data,id',
            'location_id' => 'required|exists:company_locations,id',
            'location_code' => 'required',
            'receiving_qty' => [
                'required',
                'numeric',
                'min:0',
                'max:' . $this->getMaxReceivingQty()
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receiving_qty.max' => 'You can only receive a maximum of :max quantity, not more than this.',
            'receiving_qty.required' => 'Please enter the receiving quantity.',
        ];
    }

    /**
     * Get the maximum receiving quantity based on the ordered quantity
     */
    protected function getMaxReceivingQty(): float
    {
        $purchaseOrderData = PurchaseOrderData::find($this->input('data_id'));
        $receivedQty = $purchaseOrderData->stocks()->get()->sum('qty');

        return $purchaseOrderData ?  (float) $purchaseOrderData->qty - $receivedQty : 0;
    }
}
