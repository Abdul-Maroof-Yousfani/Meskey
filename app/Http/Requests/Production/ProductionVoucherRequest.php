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

        
        return [
            'prod_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('production_vouchers', 'prod_no')->ignore($productionVoucherId)
            ],
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
            'produced_qty_kg' => [
                'required',
                'numeric',
                'min:0.01'
            ],
            'supervisor_id' => [
                'nullable',
                'exists:users,id'
            ],
            'labor_cost_per_kg' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'overhead_cost_per_kg' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'status' => [
                'required',
                'in:draft,completed,approved'
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prod_no.required' => 'Production number is required',
            'prod_no.unique' => 'This production number already exists',
            'prod_date.required' => 'Production date is required',
            'prod_date.before_or_equal' => 'Production date cannot be in the future',
            'job_order_id.required' => 'Job order is required',
            'job_order_id.exists' => 'Selected job order does not exist',
            'location_id.required' => 'Location is required',
            'location_id.exists' => 'Selected location does not exist',
            'produced_qty_kg.required' => 'Produced quantity is required',
            'produced_qty_kg.min' => 'Produced quantity must be greater than 0',
            'supervisor_id.exists' => 'Selected supervisor does not exist',
            'labor_cost_per_kg.numeric' => 'Labor cost must be a number',
            'overhead_cost_per_kg.numeric' => 'Overhead cost must be a number',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be draft, completed, or approved',
        ];
    }
}
