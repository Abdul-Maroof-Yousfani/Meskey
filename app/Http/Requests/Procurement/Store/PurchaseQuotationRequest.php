<?php

namespace App\Http\Requests\Procurement\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Procurement\Store\PurchaseQuotationData;

class PurchaseQuotationRequest extends FormRequest
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
            'purchase_date'       => 'required|date',
            'purchase_request_id' => 'required|exists:purchase_requests,id',
            'location_id'         => 'required|exists:company_locations,id',
            'supplier_id'         => 'required|exists:suppliers,id',
            'reference_no'        => 'nullable|string|max:255',
            'description'         => 'nullable|string',

            'category_id'         => 'required|array|min:1',
            'category_id.*'       => 'required|exists:categories,id',

            'item_id'             => 'required|array|min:1',
            'item_id.*'           => 'required|exists:products,id',

            'uom'                 => 'nullable|array',
            'uom.*'               => 'nullable|string|max:255',

            // 'qty'                 => 'required|array|min:1',
            // 'qty.*'               => 'required|numeric|min:0.01',

            'rate'                => 'required|array|min:1',
            'rate.*'              => 'required|numeric|min:0.01',

            // 'total'               => 'required|array|min:1',
            // 'total.*'             => 'required|numeric|min:0.01',

            // 'supplier_id'         => 'required|array',
            // 'supplier_id.*'       => [
            //     'required',
            //     'exists:suppliers,id',
            //     function ($attribute, $value, $fail) {
            //         $parts = explode('.', $attribute);
            //         $index = $parts[1] ?? null;

            //         if ($index !== null) {
            //             $itemIds = $this->input('item_id', []);
            //             $purchaseRequestId = $this->input('purchase_request_id');

            //             if (isset($itemIds[$index])) {
            //                 $itemId = $itemIds[$index];

            //                 $exists = PurchaseQuotationData::where('item_id', $itemId)
            //                     ->where('supplier_id', $value)
            //                     ->whereHas('purchase_quotation', function ($query) use ($purchaseRequestId) {
            //                         $query->where('purchase_request_id', $purchaseRequestId);
            //                     })
            //                     ->exists();

            //                 if ($exists) {
            //                     $fail('The supplier for this item already exists in a purchase quotation for this purchase request.');
            //                 }
            //             }
            //         }
            //     }
            // ],

            'remarks'             => 'nullable|array',
            'remarks.*'           => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'At least one category is required.',
            'item_id.required' => 'At least one item is required.',
            // 'qty.required' => 'At least one quantity is required.',
            'rate.required' => 'At least one rate is required.',
            // 'total.required' => 'At least one total amount is required.',
            'supplier_id.required' => 'At least one supplier is required.',

            'category_id.*.required' => 'Each category is required.',
            'item_id.*.required' => 'Each item is required.',
            // 'qty.*.required' => 'Each quantity is required.',
            'rate.*.required' => 'Each rate is required.',
            // 'total.*.required' => 'Each total amount is required.',
            'supplier_id.*.required' => 'Each supplier is required.',

            // 'qty.*.min' => 'Quantity must be at least 0.01.',
            'rate.*.min' => 'Rate must be at least 0.01.',
            // 'total.*.min' => 'Total amount must be at least 0.01.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422));
    }
}
