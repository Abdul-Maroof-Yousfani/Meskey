<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobOrderRequestFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Fallback user current company if company_id is missing
        if (!$this->has('company_id') || empty($this->company_id)) {
            $this->merge([
                'company_id' => auth()->user()?->current_company_id ?? 1
            ]);
        }

        if (empty($this->company_location_id) && !empty($this->packing_items[0]['company_location_id'])) {
            $this->merge([
                'company_location_id' => $this->packing_items[0]['company_location_id']
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'company_location_id' => [
                'required',
                'exists:company_locations,id'
            ],
            'request_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('job_order_requests')->ignore($this->route('job_order_request'))
            ],
            'request_date' => [
                'required',
                'date',
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
                'max:5000'
            ],
            'order_description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'product_id' => [
                'required',
                'exists:products,id'
            ],
            'specifications' => [
                'required',
                'array',
                'min:1'
            ],
            'specifications.*.product_slab_type_id' => [
                'required',
                'exists:product_slab_types,id'
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
            'specifications.*.value_type' => [
                'required',
                'in:min,max'
            ],
            'other_specifications' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'packing_items' => [
                'required',
                'array',
                'min:1'
            ],
            'packing_items.*.company_location_id' => [
                'nullable',
                'exists:company_locations,id'
            ],
            'packing_items.*.bag_product_id' => [
                'required',
                'exists:products,id'
            ],
            'packing_items.*.bag_condition_id' => [
                'required',
            ],
            'packing_items.*.bag_size' => [
                'required',
                'numeric',
                'min:0.01'
            ],
            'packing_items.*.no_of_bags' => [
                'required',
                'integer',
                'min:1'
            ],
            'packing_items.*.empty_bags' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.empty_bags_percentage' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.extra_bags' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.extra_bags_percentage' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'packing_items.*.brand_id' => [
                'required',
                'exists:brands,id'
            ],
            'packing_items.*.bag_color_id' => [
                'required',
                'exists:colors,id'
            ],
            'packing_items.*.thread_color_id' => [
                'nullable',
                'exists:colors,id'
            ],
            'packing_items.*.stitching_id' => [
                'nullable',
                'exists:stitchings,id'
            ],
            'packing_items.*.delivery_date' => [
                'nullable',
                'date'
            ],
            'packing_items.*.fumigation_company_id' => [
                $this->filled('export_order_id') ? 'required' : 'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if ($this->filled('export_order_id')) {
                        $validValues = array_filter((array) $value, function ($v) {
                            return $v !== 'null' && $v !== null && $v !== '';
                        });
                        if (empty($validValues)) {
                            $fail('Fumigation Company is required for each packing item when an Export Order is selected.');
                        }
                    }
                }
            ],
            'packing_items.*.fumigation_company_id.*' => [
                function ($attribute, $value, $fail) {
                    if ($value === 'null' || $value === null || $value === '') {
                        if ($this->filled('export_order_id')) {
                            $fail('N/A is not allowed for Fumigation Company when an Export Order is selected.');
                        }
                        return;
                    }
                    if (!\DB::table('fumigation_companies')->where('id', $value)->exists()) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                }
            ],
            'packing_items.*.fumigation_type' => [
                'nullable',
                'string',
                'max:150'
            ],
            'fumigation_type' => [
                'nullable',
                'string',
                'max:150'
            ],
            'inspection_company_id' => [
                'nullable',
                'array'
            ],
            'inspection_company_id.*' => [
                function ($attribute, $value, $fail) {
                    if ($value === 'null' || $value === null || $value === '') {
                        return;
                    }
                    if (!\DB::table('inspection_companies')->where('id', $value)->exists()) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                }
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
                'date'
            ],
            'packing_description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'container_protection_items' => [
                'nullable',
                'array'
            ],
            'container_protection_items.*.product_id' => [
                'required_with:container_protection_items',
                'exists:products,id'
            ],
            'container_protection_items.*.quantity_per_container' => [
                'required_with:container_protection_items',
                'numeric',
                'min:0'
            ]
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'packing_items.required' => 'At least one packing item is required.',
            'packing_items.min' => 'At least one packing item is required.',
            'packing_items.*.company_location_id.required' => 'Location is required for each packing item.',
            'packing_items.*.company_location_id.exists' => 'Selected location does not exist.',
            'packing_items.*.brand_id.required' => 'Brand is required for each packing item.',
            'packing_items.*.bag_product_id.required' => 'Bag product is required for each packing item.',
            'packing_items.*.bag_condition_id.required' => 'Bag condition is required for each packing item.',
            'packing_items.*.bag_color_id.required' => 'Bag color is required for each packing item.',
            'packing_items.*.bag_size.required' => 'Packing size (kg) is required for each packing item.',
            'packing_items.*.bag_size.numeric' => 'Packing size (kg) must be a number.',
            'packing_items.*.bag_size.min' => 'Packing size (kg) must be at least 0.01 kg.',
            'packing_items.*.no_of_bags.required' => 'No. of bags is required for each packing item.',
            'packing_items.*.no_of_bags.integer' => 'No. of bags must be an integer.',
            'packing_items.*.no_of_bags.min' => 'No. of bags must be at least 1.',
            'packing_items.*.fumigation_company_id.required' => 'Fumigation Company is required for each packing item when an Export Order is selected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_location_id' => 'company location',
            'export_order_id' => 'export order',
            'request_no' => 'request number',
            'request_date' => 'request date',
            'product_id' => 'product',
            'crop_year_id' => 'crop year',
            'packing_items' => 'packing items',
            'packing_items.*.company_location_id' => 'location',
            'packing_items.*.brand_id' => 'brand',
            'packing_items.*.bag_product_id' => 'bag product',
            'packing_items.*.bag_condition_id' => 'bag condition',
            'packing_items.*.bag_color_id' => 'bag color',
            'packing_items.*.thread_color_id' => 'thread color',
            'packing_items.*.stitching_id' => 'stitching',
            'packing_items.*.bag_size' => 'packing size (kg)',
            'packing_items.*.no_of_bags' => 'no. of bags',
            'packing_items.*.extra_bags' => 'extra bags',
            'packing_items.*.empty_bags' => 'empty bags',
            'packing_items.*.delivery_date' => 'delivery date',
            'packing_items.*.fumigation_company_id' => 'fumigation company',
            'packing_items.*.fumigation_type' => 'fumigation type',
        ];
    }
}
