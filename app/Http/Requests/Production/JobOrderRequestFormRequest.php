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
                'nullable',
                'array'
            ],
            'packing_items.*.fumigation_company_id.*' => [
                'exists:fumigation_companies,id'
            ],
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
}
