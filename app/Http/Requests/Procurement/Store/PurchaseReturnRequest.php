<?php

namespace App\Http\Requests\Procurement\Store;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
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
            'pr_no' => 'required|string|unique:purchase_returns,pr_no' . ($this->route('purchase_return') ? ',' . $this->route('purchase_return') : ''),
            'date' => 'required|date',
            'reference_no' => 'nullable|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_bill_ids' => 'required|array|min:1',
            'purchase_bill_ids.*' => 'required|exists:purchase_bills,id',
            'company_location_id' => 'required|exists:company_locations,id',
            'remarks' => 'nullable|string',
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',
            'bill_data_id' => 'required|array|min:1',
            'bill_data_id.*' => 'required|exists:purchase_bills_data,id',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|numeric|min:0.001',
            'rate' => 'required|array|min:1',
            'rate.*' => 'required|numeric|min:0',
            'gross_amount' => 'required|array|min:1',
            'gross_amount.*' => 'required|numeric|min:0',
            'net_amount' => 'required|array|min:1',
            'net_amount.*' => 'required|numeric|min:0',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'packing' => 'nullable|array',
            'packing.*' => 'nullable|string',
            'no_of_bags' => 'nullable|array',
            'no_of_bags.*' => 'nullable|integer|min:0',
        ];
    }
}
