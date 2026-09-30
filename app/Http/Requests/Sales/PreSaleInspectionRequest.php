<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class PreSaleInspectionRequest extends FormRequest
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
        if ($this->has('arrival_location_id') && is_array($this->arrival_location_id)) {
            $this->merge([
                'arrival_location_id' => array_map(fn($v) => empty($v) ? null : $v, $this->arrival_location_id),
            ]);
        }
        if ($this->has('arrival_sub_location_id') && is_array($this->arrival_sub_location_id)) {
            $this->merge([
                'arrival_sub_location_id' => array_map(fn($v) => empty($v) ? null : $v, $this->arrival_sub_location_id),
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
        return [
            'inspection_no' => 'nullable|string|max:100',
            'date' => 'required|date',
            'location_id' => 'required|integer|exists:company_locations,id',
            'party_name' => 'required|string|max:255',
            'party_contact_no' => 'nullable|string|max:50',
            'reference' => 'nullable|string',
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',
            'arrival_location_id' => 'nullable|array',
            'arrival_location_id.*' => 'nullable|integer|exists:arrival_locations,id',
            'arrival_sub_location_id' => 'nullable|array',
            'arrival_sub_location_id.*' => 'nullable|integer|exists:arrival_sub_locations,id',
            'weight' => 'required|array|min:1',
            'weight.*' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'inspection_no.required' => 'Inspection Unique No is required.',
            'date.required' => 'Inspection Date is required.',
            'location_id.required' => 'Location is required.',
            'party_name.required' => 'Party Name is required.',
            'item_id.required' => 'At least one Item (product) is required.',
            'item_id.*.required' => 'Item (product) is required for each row.',
            'weight.*.required' => 'Item weight is required for each row.',
            'weight.*.numeric' => 'Item weight must be a valid number.',
        ];
    }
}
