<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobOrderRequest extends FormRequest
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
        $rules = [
            // Basic Information Validation
            'job_order_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('job_orders')->ignore($this->route('job_order'))
            ],
            'job_order_date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'location' => [
                'required',
                'exists:company_locations,id'
            ],
            'ref_no' => [
                'nullable',
                'string',
                'max:100'
            ],
            'attention_to' => [
                'nullable',
                'array'
            ],
            'attention_to.*' => [
                'exists:users,id'
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:500'
            ],
            'order_description' => [
                'nullable',
                'string',
                'max:1000'
            ],

            // Product Validation
            'product_id' => [
                'required',
                'exists:products,id'
            ],

            // Specifications Validation
            'specifications' => [
                'required',
                'array',
                'min:1'
            ],
            'specifications.*.product_slab_id' => [
                'required',
                'exists:product_slabs,id'
            ],
            'specifications.*.spec_name' => [
                'required',
                'string',
                'max:255'
            ],
            'specifications.*.spec_value' => [
                'required',
                'string',
                'max:100'
            ],
            'specifications.*.uom' => [
                'nullable',
                'string',
                'max:50'
            ],

            // Packing Items Validation
            'packing_items' => [
                'required',
                'array',
                'min:1'
            ],
            'packing_items.*.bag_type' => [
                'required',
                'string',
                'max:100'
            ],
            'packing_items.*.bag_condition' => [
                'required',
                'string',
                'max:100'
            ],
            'packing_items.*.bag_size' => [
                'required',
                'numeric',
                'min:0.1',
                'max:1000'
            ],
            'packing_items.*.no_of_bags' => [
                'required',
                'integer',
                'min:1',
                'max:100000'
            ],
            'packing_items.*.extra_bags' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000'
            ],
            'packing_items.*.empty_bags' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000'
            ],
            'packing_items.*.total_bags' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'packing_items.*.total_kgs' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.metric_tons' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.stuffing_in_container' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.no_of_containers' => [
                'nullable',
                'integer',
                'min:0',
                'max:100'
            ],
            'packing_items.*.brand' => [
                'required',
                'string',
                'max:100'
            ],
            'packing_items.*.bag_color' => [
                'required',
                'string',
                'max:50'
            ],
            'packing_items.*.min_weight_empty_bags' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            // Operational Details Validation
            'inspection_company_id' => [
                'nullable',
                'array'
            ],
            'inspection_company_id.*' => [
                'exists:inspection_companies,id'
            ],
            'fumigation_company_id' => [
                'nullable',
                'array'
            ],
            'fumigation_company_id.*' => [
                'exists:fumigation_companies,id'
            ],
            'arrival_locations' => [
                'nullable',
                'array'
            ],
            'arrival_locations.*' => [
                'exists:arrival_locations,id'
            ],
            'delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:job_order_date'
            ],
            'loading_date' => [
                'nullable',
                'date',
                'after_or_equal:job_order_date'
            ],
            'packing_description' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Basic Information Messages
            'job_order_no.required' => 'Job order number is required',
            'job_order_no.unique' => 'This job order number already exists',
            'job_order_date.required' => 'Job order date is required',
            'job_order_date.before_or_equal' => 'Job order date cannot be in the future',
            'location.required' => 'Location is required',
            'location.exists' => 'Selected location does not exist',

            // Product Messages
            'product_id.required' => 'Product selection is required',
            'product_id.exists' => 'Selected product does not exist',

            // Specifications Messages
            'specifications.required' => 'At least one specification is required',
            'specifications.min' => 'At least one specification is required',
            'specifications.*.product_slab_id.required' => 'Specification ID is required',
            'specifications.*.spec_name.required' => 'Specification name is required',
            'specifications.*.spec_value.required' => 'Specification value is required',

            // Packing Items Messages
            'packing_items.required' => 'At least one packing item is required',
            'packing_items.min' => 'At least one packing item is required',
            'packing_items.*.bag_type.required' => 'Bag type is required for all packing items',
            'packing_items.*.bag_condition.required' => 'Bag condition is required for all packing items',
            'packing_items.*.bag_size.required' => 'Bag size is required for all packing items',
            'packing_items.*.bag_size.min' => 'Bag size must be at least 0.1 kg',
            'packing_items.*.no_of_bags.required' => 'Number of bags is required for all packing items',
            'packing_items.*.no_of_bags.min' => 'Number of bags must be at least 1',
            'packing_items.*.brand.required' => 'Brand is required for all packing items',
            'packing_items.*.bag_color.required' => 'Bag color is required for all packing items',

            // Operational Details Messages
            'delivery_date.after_or_equal' => 'Delivery date must be on or after job order date',
            'loading_date.after_or_equal' => 'Loading date must be on or after job order date',

            // Array Validation Messages
            'attention_to.*.exists' => 'One or more selected users do not exist',
            'inspection_company_id.*.exists' => 'One or more selected inspection companies do not exist',
            'fumigation_company_id.*.exists' => 'One or more selected fumigation companies do not exist',
            'arrival_locations.*.exists' => 'One or more selected arrival locations do not exist',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'job_order_no' => 'job order number',
            'job_order_date' => 'job order date',
            'location' => 'location',
            'product_id' => 'product',
            'specifications' => 'specifications',
            'packing_items' => 'packing items',
            'attention_to' => 'attention to',
            'inspection_company_id' => 'inspection company',
            'fumigation_company_id' => 'fumigation company',
            'arrival_locations' => 'arrival locations',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure arrays are properly formatted even if empty
        $this->merge([
            'attention_to' => $this->attention_to ?? [],
            'inspection_company_id' => $this->inspection_company_id ?? [],
            'fumigation_company_id' => $this->fumigation_company_id ?? [],
            'arrival_locations' => $this->arrival_locations ?? [],
            'specifications' => $this->specifications ?? [],
            'packing_items' => $this->packing_items ?? [],
        ]);
    }
}