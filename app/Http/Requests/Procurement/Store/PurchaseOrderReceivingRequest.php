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
            'receiving_date' => 'required|date',
            'purchase_request_id' => 'required|exists:purchase_requests,id',
            'location_id' => 'required|exists:company_locations,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'dc_no' => "required",
            "truck_no" => "required",

            'category_id' => 'required|array|min:1',
            'category_id.*' => 'required|exists:categories,id',

            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',

            // 'supplier_id' => 'required|array|min:1',
            // 'supplier_id.*' => 'required|exists:suppliers,id',

            'uom' => 'nullable|array',
            'uom.*' => 'nullable|string|max:255',

            // 'qty' => 'required|array|min:1|max:$this->getMaxReceivingQty()',
            'qty' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',

            // 'rate' => 'required|array|min:1',
            // 'rate.*' => 'required|numeric|min:0.01',

            'remarks' => 'nullable|array',
            'remarks.*' => 'nullable|string|max:1000',

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
            'receiving_date.required' => 'The purchase order receiving date field is required.',
            'receiving_date.date' => 'The purchase order date receiving must be a valid date.',
            'dc_no.required' => "The DC No field is required",
            "truck_no" => "The Truck No field is required",

            'purchase_request_id.required' => 'The purchase request field is required.',
            'purchase_request_id.exists' => 'The selected purchase request is invalid.',

            'location_id.required' => 'The location field is required.',
            'location_id.exists' => 'The selected location is invalid.',

            'supplier_id.required' => 'The supplier field is required.',
            'supplier_id.exists' => 'The selected supplier is invalid.',

            'reference_no.string' => 'The reference number must be a string.',
            'reference_no.max' => 'The reference number may not be greater than 255 characters.',

            'description.string' => 'The description must be a string.',

            'category_id.required' => 'At least one category is required.',
            'category_id.array' => 'The categories must be in array format.',
            'category_id.min' => 'At least one category must be selected.',
            'category_id.*.required' => 'Each category is required.',
            'category_id.*.exists' => 'One or more selected categories are invalid.',

            'item_id.required' => 'At least one item is required.',
            'item_id.array' => 'The items must be in array format.',
            'item_id.min' => 'At least one item must be selected.',
            'item_id.*.required' => 'Each item is required.',
            'item_id.*.exists' => 'One or more selected items are invalid.',

            'uom.array' => 'The UOM field must be an array.',
            'uom.*.string' => 'Each UOM must be a string.',
            'uom.*.max' => 'Each UOM may not be greater than 255 characters.',

            'qty.required' => 'At least one quantity is required.',
            'qty.array' => 'The quantities must be in array format.',
            'qty.min' => 'At least one quantity must be provided.',
            'qty.*.required' => 'Each quantity is required.',
            'qty.*.numeric' => 'Each quantity must be a number.',
            'qty.*.min' => 'Each quantity must be at least 0.01.',
            'qty.max' => 'You can only receive a maximum of :max quantity, not more than this.',

            'rate.required' => 'At least one rate is required.',
            'rate.array' => 'The rates must be in array format.',
            'rate.min' => 'At least one rate must be provided.',
            'rate.*.required' => 'Each rate is required.',
            'rate.*.numeric' => 'Each rate must be a number.',
            'rate.*.min' => 'Each rate must be at least 0.01.',

            'receive_weight.*.required' => "Each Receive Weight is required",

            'remarks.array' => 'The remarks must be in array format.',
            'remarks.*.string' => 'Each remark must be a string.',
            'remarks.*.max' => 'Each remark may not be greater than 1000 characters.',

        ];
    }
    protected function prepareForValidation()
    {
        $this->ensureArrayCountsMatch();
    }

    /**
     * Ensure all array fields have the same number of elements.
     */
    protected function ensureArrayCountsMatch()
    {
        $arrayFields = [
            'category_id',
            'item_id',
            // 'supplier_id',
            'qty',
            'rate'
        ];

        $count = null;
        foreach ($arrayFields as $field) {
            if ($this->has($field) && is_array($this->$field)) {
                if ($count === null) {
                    $count = count($this->$field);
                } elseif (count($this->$field) !== $count) {
                    $this->validator->errors()->add(
                        $field,
                        "The number of $field must match the number of other array fields."
                    );
                }
            }
        }
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Add additional processing if needed
        return $validated;
    }
    /**
     * Get the maximum receiving quantity based on the ordered quantity
     */
    protected function getMaxReceivingQty(): float
    {
        $purchaseOrderData = PurchaseOrderData::find($this->input('data_id'));
        $receivedQty = $purchaseOrderData->stocks()->get()->sum('qty');

        return $purchaseOrderData ? (float) $purchaseOrderData->qty - $receivedQty : 0;
    }
}
