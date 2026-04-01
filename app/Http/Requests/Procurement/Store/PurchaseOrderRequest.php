<?php

namespace App\Http\Requests\Procurement\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'delivery_address' => "nullable",
            'purchase_date' => 'required|date',
            'purchase_request_id' => 'required|exists:purchase_requests,id',
            'payment_term_id' => 'required|exists:payment_terms,id',
            'location_id' => 'required|exists:company_locations,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            'category_id' => 'required|array|min:1',
            'category_id.*' => 'required|exists:categories,id',

            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',

            // 'supplier_id' => 'required|array|min:1',
            // 'supplier_id.*' => 'required|exists:suppliers,id',

            'uom' => 'nullable|array',
            'uom.*' => 'nullable|string|max:255',

            'qty' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',

            'rate' => 'required|array|min:1',
            'rate.*' => 'required|numeric|min:0.01',

            'remarks' => 'nullable|array',
            'remarks.*' => 'nullable|string|max:1000',

            'micron' => 'nullable|array',
            'micron.*' => 'nullable|string|max:1000',

            'excise_duty' => 'nullable|array',
            'excise_duty.*' => 'nullable|numeric|min:0',

            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',

            'purchase_request_data_id' => 'required|array|min:1',
            'purchase_request_data_id.*' => 'required|exists:purchase_request_data,id',

            'use_quotation' => 'sometimes|array',
            'use_quotation.*' => 'sometimes|boolean',

            // 'quotation_ids' => 'sometimes|array',
            // 'quotation_ids.*' => 'sometimes|exists:purchase_quotation_data,id',
            'purchase_quotation_data_id' => 'nullable|array',
            'purchase_quotation_data_id.*' => 'nullable|exists:purchase_quotation_data,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'purchase_date.required' => 'The purchase order date field is required.',
            'purchase_date.date' => 'The purchase order date must be a valid date.',

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

            // 'supplier_id.required' => 'At least one supplier is required.',
            // 'supplier_id.array' => 'The suppliers must be in array format.',
            // 'supplier_id.min' => 'At least one supplier must be selected.',
            // 'supplier_id.*.required' => 'Each supplier is required.',
            // 'supplier_id.*.exists' => 'One or more selected suppliers are invalid.',

            'uom.array' => 'The UOM field must be an array.',
            'uom.*.string' => 'Each UOM must be a string.',
            'uom.*.max' => 'Each UOM may not be greater than 255 characters.',

            'qty.required' => 'At least one quantity is required.',
            'qty.array' => 'The quantities must be in array format.',
            'qty.min' => 'At least one quantity must be provided.',
            'qty.*.required' => 'Each quantity is required.',
            'qty.*.numeric' => 'Each quantity must be a number.',
            'qty.*.min' => 'Each quantity must be at least 0.01.',

            'rate.required' => 'At least one rate is required.',
            'rate.array' => 'The rates must be in array format.',
            'rate.min' => 'At least one rate must be provided.',
            'rate.*.required' => 'Each rate is required.',
            'rate.*.numeric' => 'Each rate must be a number.',
            'rate.*.min' => 'Each rate must be at least 0.01.',

            'remarks.array' => 'The remarks must be in array format.',
            'remarks.*.string' => 'Each remark must be a string.',
            'remarks.*.max' => 'Each remark may not be greater than 1000 characters.',

            // 'purchase_request_data_id.*.exists' => 'One or more purchase request data items are invalid.',
            // 'purchase_quotation_data_id.*.exists' => 'One or more purchase quotation data items are invalid.',
            // 'quotation_ids.*.exists' => 'One or more quotation IDs are invalid.',
        ];
    }
    
    // protected function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $arrayFields = [
    //             'category_id',
    //             'item_id',
    //             'supplier_id',
    //             'qty',
    //             'rate',
    //         ];

    //         $count = null;

    //         foreach ($arrayFields as $field) {
    //             if ($this->has($field) && is_array($this->$field)) {
    //                 if ($count === null) {
    //                     $count = count($this->$field);
    //                 } elseif (count($this->$field) !== $count) {
    //                     $validator->errors()->add(
    //                         $field,
    //                         "The number of $field entries must match the number of other array fields."
    //                     );
    //                 }
    //             }
    //         }
    //     });
    // }

    /**
     * Prepare the data for validation.
     */
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $orderId = $this->route('purchase_order'); // Get ID if it's an update route

            // Pre-calculate item occurrences to check for duplicates
            $itemIds = $this->input('item_id', []);
            $filteredItemIds = array_filter($itemIds, function($id) {
                return !is_null($id) && $id !== '';
            });
            $occurrences = array_count_values($filteredItemIds);

            foreach ($this->qty as $index => $qty) {
                $prDataId = $this->purchase_request_data_id[$index] ?? null;
                $pqDataId = $this->purchase_quotation_data_id[$index] ?? null;
                $itemId = $this->item_id[$index] ?? null;
                
                // 1. Check for duplicate items
                if ($itemId && isset($occurrences[$itemId]) && $occurrences[$itemId] > 1) {
                    $validator->errors()->add(
                        "item_id.{$index}",
                        'The same item cannot be added multiple times.'
                    );
                }

                if (!$prDataId) continue;

                $prData = \App\Models\Procurement\Store\PurchaseRequestData::find($prDataId);
                if (!$prData) {
                    $validator->errors()->add("qty.$index", "Invalid purchase request reference.");
                    continue;
                }

                // 2. Calculate PR Remaining Balance
                $prTotal = (float)$prData->qty;
                $prOrderedQuery = \App\Models\Procurement\Store\PurchaseOrderData::where('purchase_request_data_id', $prDataId)
                                    ->where('am_approval_status', '!=', 'rejected')
                                    ->whereHas('purchase_order', function($q) {
                                        $q->where('am_approval_status', '!=', 'rejected');
                                    });
                if ($orderId) {
                    $prOrderedQuery->where('purchase_order_id', '!=', $orderId);
                }
                $prOrdered = (float)$prOrderedQuery->sum('qty');
                $prRemaining = $prTotal - $prOrdered;

                // 3. Calculate PQ Remaining Balance (if applicable)
                $pqRemaining = 999999999; // Default to large number if no PQ
                if ($pqDataId && $pqDataId != 0 && $pqDataId != "") {
                    $pqData = \App\Models\Procurement\Store\PurchaseQuotationData::find($pqDataId);
                    if ($pqData) {
                        $pqTotal = (float)$pqData->qty;
                        $pqOrderedQuery = \App\Models\Procurement\Store\PurchaseOrderData::where('purchase_quotation_data_id', $pqDataId)
                                            ->where('am_approval_status', '!=', 'rejected')
                                            ->whereHas('purchase_order', function($q) {
                                                $q->where('am_approval_status', '!=', 'rejected');
                                            });
                        if ($orderId) {
                            $pqOrderedQuery->where('purchase_order_id', '!=', $orderId);
                        }
                        $pqOrdered = (float)$pqOrderedQuery->sum('qty');
                        $pqRemaining = $pqTotal - $pqOrdered;
                    }
                }

                // 4. Final Limit (Minimum of both)
                $remaining = min($prRemaining, $pqRemaining);
                
                // Using a small epsilon for float comparison to avoid precision issues
                if ((float)$qty > ($remaining + 0.0001)) {
                    $itemName = $prData->item->name ?? "Item " . ($index + 1);
                    $sourceType = ($pqRemaining < $prRemaining) ? "Purchase Quotation" : "Purchase Request";
                    $validator->errors()->add("qty.$index", "Ordered quantity for '{$itemName}' exceeds the allowed {$sourceType} balance. Remaining: " . round($remaining, 2));
                }
            }
        });
    }
}
