<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionVoucherRequest extends FormRequest
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
        $productionVoucherId = $this->route('production_voucher');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'prod_date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'job_order_id' => [
                'required',
                'array',
                'min:1'
            ],
            'job_order_id.*' => [
                'exists:job_orders,id'
            ],
            'location_id' => [
                'required',
                'exists:company_locations,id'
            ],
            'product_id' => [
                'required',
                'exists:products,id'
            ],
            'plant_id' => [
                'nullable',
                'exists:plants,id'
            ],
            'by_product_id' => [
                'nullable',
                'exists:products,id'
            ],
            'remarks' => [
                'nullable',
                // 'string',
                'max:1000'
            ],
            // Production Inputs
            'product_id.*' => [
                'nullable',
                'exists:products,id'
            ],
            'location_id.*' => [
                'nullable',
                'exists:arrival_sub_locations,id'
            ],
            'qty.*' => [
                'nullable',
                'numeric',
                'min:0.01'
            ],
            'remarks.*' => [
                'nullable',
                'string',
                'max:1000'
            ],
            // Production Outputs
            'output_product_id.*' => [
                'nullable',
                'exists:products,id'
            ],
            'output_qty.*' => [
                'nullable',
                'numeric',
                'min:0.01'
            ],
            'output_no_of_bags.*' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'output_bag_size.*' => [
                'nullable',
                'string',
                'max:50'
            ],
            'output_avg_weight_per_bag.*' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'output_yield.*' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'output_arrival_sub_location_id.*' => [
                'nullable',
                'exists:arrival_sub_locations,id'
            ],
            'output_brand_id.*' => [
                'nullable',
                'exists:brands,id'
            ],
            'output_job_order_id.*' => [
                'nullable',
                'exists:job_orders,id'
            ],
            'output_remarks.*' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];

        // Only require prod_no on create
        if (!$isUpdate) {
            $rules['prod_no'] = [
                'nullable',
                'string',
                'max:100',
                Rule::unique('production_vouchers', 'prod_no')
            ];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Get by product ID
            $byProductId = $this->input('by_product_id');
            
            // If no by product is selected, skip validation
            if (empty($byProductId)) {
                return;
            }
            
            // Get all output rows
            $outputQtys = $this->input('output_qty', []);
            $outputNoOfBags = $this->input('output_no_of_bags', []);
            $outputProductIds = $this->input('output_product_id', []);
            $outputBagSizes = $this->input('output_bag_size', []);
            $outputStorageLocations = $this->input('output_arrival_sub_location_id', []);
            $outputBrandIds = $this->input('output_brand_id', []);
            $outputJobOrderIds = $this->input('output_job_order_id', []);
            
            // Validate by product rows: if no_of_bags is provided, other fields are required
            foreach ($outputNoOfBags as $index => $noOfBags) {
                // Check if this row is a by product row (product_id matches by_product_id)
                $isByProductRow = false;
                if (!empty($outputProductIds[$index])) {
                    $isByProductRow = ($outputProductIds[$index] == $byProductId);
                }
                
                // If it's a by product row and no_of_bags is provided (and > 0), make other fields required
                if ($isByProductRow && !empty($noOfBags) && $noOfBags > 0) {
                    if (empty($outputQtys[$index]) || $outputQtys[$index] <= 0) {
                        $validator->errors()->add("output_qty.{$index}", "Quantity is required when number of bags is provided for by product.");
                    }
                    
                    if (empty($outputBagSizes[$index])) {
                        $validator->errors()->add("output_bag_size.{$index}", "Bag size is required when number of bags is provided for by product.");
                    }
                    
                    if (empty($outputStorageLocations[$index])) {
                        $validator->errors()->add("output_arrival_sub_location_id.{$index}", "Storage location is required when number of bags is provided for by product.");
                    }
                    
                    if (empty($outputBrandIds[$index])) {
                        $validator->errors()->add("output_brand_id.{$index}", "Brand is required when number of bags is provided for by product.");
                    }
                    
                    if (empty($outputJobOrderIds[$index])) {
                        $validator->errors()->add("output_job_order_id.{$index}", "Job order is required when number of bags is provided for by product.");
                    }
                }
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prod_no.unique' => 'This production number already exists',
            'prod_date.required' => 'Production date is required',
            'prod_date.before_or_equal' => 'Production date cannot be in the future',
            'job_order_id.required' => 'Job order is required',
            'job_order_id.min' => 'At least one job order is required',
            'job_order_id.*.exists' => 'Selected job order does not exist',
            'location_id.required' => 'Location is required',
            'location_id.exists' => 'Selected location does not exist',
            'product_id.required' => 'Head product is required',
            'product_id.exists' => 'Selected head product does not exist',
            'plant_id.exists' => 'Selected plant does not exist',
            'by_product_id.exists' => 'Selected by product does not exist',
        ];
    }
}
