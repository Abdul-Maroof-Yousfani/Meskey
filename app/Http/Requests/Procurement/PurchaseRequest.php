<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'purchase_date'         => 'required|date',
            'company_location_id'   => 'required|exists:company_locations,id',
            'reference_no'          => 'nullable|string|max:255',
            'description'           => 'nullable|string',

            'category_id_header'    => 'required|exists:categories,id',

            'item_id'               => 'required|array|min:1',
            'item_id.*'             => [
                'required',
                'exists:products,id',
                // Custom rule to prevent duplicate items within same category
                function ($attribute, $value, $fail) {
                    $currentCategoryId = $this->input("category_id_header");

                    // Get all item IDs
                    $itemIds = $this->input('item_id', []);
                    
                    // Check for duplicates of the same item
                    $occurrences = array_count_values($itemIds);

                    if (isset($occurrences[$value]) && $occurrences[$value] > 1) {
                        $fail('The same item cannot be added multiple times.');
                    }
                }
            ],

            'uom'                   => 'nullable|array',
            'uom.*'                 => 'nullable|string|max:255',

            'size'                  => 'required_if:category_id_header,38',
            'size.*'                => 'required_if:category_id_header,38',

            'brands'                => 'required_if:category_id_header,38',
            'brands.*'              => 'required_if:category_id_header,38',

            'qty'                   => 'required|array|min:1',
            'qty.*'                 => 'required|numeric|min:0.01',

            'job_order_id'          => 'nullable|array',
            'job_order_id.*'        => 'nullable|exists:job_orders,id',

            'remarks'               => 'nullable|array',
            'remarks.*'             => 'nullable|string|max:1000',

            'micron'                => 'required_if:category_id_header,38',
            'micron.*'              => 'required_if:category_id_header,38', 

            'packing_id'            => 'nullable',
            'module_type'           => 'nullable'

        ];
    }

    public function messages()
    {
        return [
            'purchase_date.required' => 'The purchase date is required.',
            'purchase_date.date' => 'The purchase date must be a valid date.',

            'company_location_id.required' => 'The location is required.',
            'company_location_id.exists' => 'The selected location is invalid.',

            'reference_no.string' => 'The reference number must be a string.',
            'reference_no.max' => 'The reference number may not be greater than 255 characters.',

            'description.string' => 'The description must be a string.',

            'category_id_header.required' => 'The category field is required.',
            'category_id_header.exists' => 'The selected category is invalid.',

            'item_id.required' => 'At least one item is required.',
            'item_id.array' => 'The item field must be an array.',
            'item_id.min' => 'At least one item must be selected.',
            'item_id.*.required' => 'Each item is required.',
            'item_id.*.exists' => 'The selected item is invalid.',

            'uom.array' => 'The UOM field must be an array.',
            'uom.*.string' => 'Each UOM must be a string.',
            'uom.*.max' => 'Each UOM may not be greater than 255 characters.',

            'qty.required' => 'At least one quantity is required.',
            'qty.array' => 'The quantity field must be an array.',
            'qty.min' => 'At least one quantity must be provided.',
            'qty.*.required' => 'Each quantity is required.',
            'qty.*.numeric' => 'Each quantity must be a number.',
            'qty.*.min' => 'Each quantity must be at least 0.01.',

            'job_order_id.array' => 'The job order field must be an array.',
            'job_order_id.*.exists' => 'The selected job order is invalid.',

            'remarks.array' => 'The remarks field must be an array.',
            'remarks.*.string' => 'Each remark must be a string.',
            'remarks.*.max' => 'Each remark may not be greater than 1000 characters.',

            'brands.required' => "At least one brand is required"
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $itemIds = $this->input('item_id', []);
            $occurrences = array_count_values($itemIds);

            foreach ($itemIds as $index => $itemId) {
                if (isset($occurrences[$itemId]) && $occurrences[$itemId] > 1) {
                    $validator->errors()->add(
                        "item_id.{$index}",
                        'The same item cannot be added multiple times.'
                    );
                }
            }
        });
    }
}
