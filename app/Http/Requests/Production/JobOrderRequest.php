<?php

namespace App\Http\Requests\Production;

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
            'crop_year_id' => [
                'required',
                'exists:crop_years,id'
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
            'specifications.*.product_slab_type_id' => [
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
            'packing_items.*.bag_type_id' => [
                'required',
                'exists:bag_types,id'
            ],
            'packing_items.*.bag_condition_id' => [
                'required',
                'string',
                'max:100'
            ],
            'packing_items.*.sub_items' => [
                'nullable',
                'array'
            ],
            'packing_items.*.sub_items.*.bag_product_id' => [
                'required',
                'exists:products,id'
            ],
            'packing_items.*.sub_items.*.bag_size_id' => [
                'required',
                'exists:sizes,id'
            ],
            // 'packing_items.*.sub_items.*.bag_size' => [
            //     'required',
            //     'numeric',
            //     'min:0.1',
            //     'max:1000'
            // ],
            'packing_items.*.sub_items.*.no_of_bags' => [
                'required',
                'integer',
                'min:1',
                'max:100000'
            ],
            'packing_items.*.sub_items.*.empty_bags' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'packing_items.*.sub_items.*.extra_bags' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'packing_items.*.sub_items.*.empty_bag_weight' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.sub_items.*.total_bags' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'packing_items.*.sub_items.*.bag_color_id' => [
                'nullable',
                'exists:colors,id'
            ],
            'packing_items.*.sub_items.*.brand_id' => [
                'nullable',
                'exists:brands,id'
            ],
            'packing_items.*.sub_items.*.thread_color_id' => [
                'nullable',
                'exists:colors,id'
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
            'packing_items.*.brand_id' => [
                'required',
                'string',
                'max:100'
            ],
            'packing_items.*.bag_color_id' => [
                'required',
                'string',
                'max:50'
            ],
            'packing_items.*.min_weight_empty_bags' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.company_location_id' => [
                'required',
                'exists:company_locations,id'
            ],
            'packing_items.*.no_of_containers' => [
                'nullable',
                'integer',
                'min:0',
                'max:100'
            ],
            'packing_items.*.description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'packing_items.*.location_instruction' => [
                'nullable',
                'string',
                'max:1000'
            ],
            // Delivery Date moved to packing items
            'packing_items.*.delivery_date' => [
                'required',
                'date',
                'after_or_equal:job_order_date'
            ],
            // Fumigation Company moved to packing items
            'packing_items.*.fumigation_company_id' => [
                'nullable',
                'array'
            ],
            'packing_items.*.fumigation_company_id.*' => [
                'exists:fumigation_companies,id'
            ],

            // Operational Details Validation (without delivery_date and fumigation_company_id)
            'inspection_company_id' => [
                'nullable',
                'array'
            ],
            'inspection_company_id.*' => [
                'exists:inspection_companies,id'
            ],
            'arrival_locations' => [
                'nullable',
                'array'
            ],
            'arrival_locations.*' => [
                'exists:arrival_locations,id'
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
            'crop_year_id.required' => 'Crop Year is required',
            'crop_year_id.exists' => 'Selected Crop Year does not exist',

            // Product Messages
            'product_id.required' => 'Product selection is required',
            'product_id.exists' => 'Selected product does not exist',

            // Specifications Messages
            'specifications.required' => 'At least one specification is required',
            'specifications.min' => 'At least one specification is required',
            'specifications.*.product_slab_type_id.required' => 'Specification ID is required',
            'specifications.*.spec_name.required' => 'Specification name is required',
            'specifications.*.spec_value.required' => 'Specification value is required',

            // Packing Items Messages
            'packing_items.required' => 'At least one packing item is required',
            'packing_items.min' => 'At least one packing item is required',
            'packing_items.*.bag_type_id.required' => 'Bag type is required for all packing items',
            'packing_items.*.bag_type_id.exists' => 'Selected bag type does not exist',
            'packing_items.*.bag_condition_id.required' => 'Bag condition is required for all packing items',
            'packing_items.*.sub_items.*.bag_type_id.required' => 'Bag type is required for master packing item',
           
            'packing_items.*.sub_items.*.bag_product_id.required' => 'Bag product is required for master packing item',
            'packing_items.*.sub_items.*.bag_product_id.exists' => 'Selected bag product does not exist',
            'packing_items.*.sub_items.*.bag_size_id.required' => 'Bag size is required for master packing item',
            'packing_items.*.sub_items.*.bag_size_id.exists' => 'Selected bag size does not exist',
            'packing_items.*.sub_items.*.bag_size.min' => 'Bag size must be at least 0.1 kg',
            'packing_items.*.sub_items.*.no_of_bags.required' => 'Number of bags is required for master packing item',
            'packing_items.*.sub_items.*.no_of_bags.min' => 'Number of bags must be at least 1',
            'packing_items.*.brand_id.required' => 'Brand is required for all packing items',
            'packing_items.*.bag_color_id.required' => 'Bag color is required for all packing items',
            'packing_items.*.company_location_id.required' => 'Company location is required for all packing items',
            'packing_items.*.company_location_id.exists' => 'Selected company location does not exist',
            'packing_items.*.min_weight_empty_bags.required' => 'Minimum weight for empty bags is required',
            // New packing items messages for delivery_date and fumigation_company_id
            'packing_items.*.delivery_date.after_or_equal' => 'Delivery date must be on or after job order date',
            'packing_items.*.fumigation_company_id.exists' => 'Selected fumigation company does not exist',

            // Operational Details Messages
            'loading_date.after_or_equal' => 'Loading date must be on or after job order date',

            // Array Validation Messages
            'attention_to.*.exists' => 'One or more selected users do not exist',
            'inspection_company_id.*.exists' => 'One or more selected inspection companies do not exist',
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
            'crop_year_id' => 'crop year',
            'product_id' => 'product',
            'specifications' => 'specifications',
            'packing_items' => 'packing items',
            'attention_to' => 'attention to',
            'inspection_company_id' => 'inspection company',
            'arrival_locations' => 'arrival locations',
            'packing_items.*.company_location_id' => 'company location',
            'packing_items.*.delivery_date' => 'delivery date',
            'packing_items.*.fumigation_company_id' => 'fumigation company',
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
            'arrival_locations' => $this->arrival_locations ?? [],
            'specifications' => $this->specifications ?? [],
            'packing_items' => $this->packing_items ?? [],
        ]);

        // Ensure each packing item has delivery_date and fumigation_company_id
        if ($this->has('packing_items')) {
            $packingItems = $this->packing_items;
            foreach ($packingItems as &$item) {
                $item['delivery_date'] = $item['delivery_date'] ?? null;
                $item['fumigation_company_id'] = $item['fumigation_company_id'] ?? null;
            }
            $this->merge(['packing_items' => $packingItems]);
        }
    }
}