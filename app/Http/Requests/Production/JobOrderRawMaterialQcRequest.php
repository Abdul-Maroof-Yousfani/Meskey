<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobOrderRawMaterialQcRequest extends FormRequest
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
            'qc_date' => 'required|date',
            'job_order_id' => 'required|exists:job_orders,id',
            'company_location_id' => 'required|exists:company_locations,id',
            'mill' => 'nullable|string|max:255',
            'commodities' => 'required|array|min:1',
            'commodities.*' => 'exists:products,id',
            'qc_data' => 'required|array',
            'qc_data.*.locations' => 'required|array|min:1',
            'qc_data.*.locations.*.sublocation_id' => 'required|exists:arrival_sub_locations,id',
            'qc_data.*.locations.*.suggested_quantity' => 'required|numeric|min:0',
            'qc_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('job_order_raw_material_qcs')->ignore($this->route('job_order_rm_qc'))
            ],
        ];
        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'qc_no.required' => 'QC number is required',
            'qc_no.unique' => 'QC number already exists',
            'qc_date.required' => 'QC date is required',
            'qc_date.date' => 'Invalid date format',
            'job_order_id.required' => 'Job order is required',
            'job_order_id.exists' => 'Selected job order does not exist',
            'company_location_id.required' => 'Location is required',
            'company_location_id.exists' => 'Selected location does not exist',
            'mill.max' => 'Mill name cannot exceed 255 characters',
            'commodities.required' => 'At least one commodity is required',
            'commodities.min' => 'At least one commodity is required',
            'commodities.*.exists' => 'Selected commodity does not exist',
            'qc_data.required' => 'QC data is required',
            'qc_data.*.locations.required' => 'At least one location is required for each commodity',
            'qc_data.*.locations.*.sublocation_id.required' => 'Sublocation is required',
            'qc_data.*.locations.*.sublocation_id.exists' => 'Selected sublocation does not exist',
            'qc_data.*.locations.*.suggested_quantity.required' => 'Suggested quantity is required',
            'qc_data.*.locations.*.suggested_quantity.numeric' => 'Suggested quantity must be a number',
            'qc_data.*.locations.*.suggested_quantity.min' => 'Suggested quantity cannot be negative',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure commodities is always an array
        if ($this->has('commodities') && !is_array($this->commodities)) {
            $this->merge([
                'commodities' => json_decode($this->commodities, true) ?? []
            ]);
        }

        // Ensure qc_data is always an array
        if ($this->has('qc_data') && !is_array($this->qc_data)) {
            $this->merge([
                'qc_data' => json_decode($this->qc_data, true) ?? []
            ]);
        }
    }
}