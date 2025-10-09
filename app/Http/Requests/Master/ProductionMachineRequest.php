<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionMachineRequest extends FormRequest
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
        return [
            'company_id' => 'required|exists:companies,id',
            'company_location_id' => 'required|exists:company_locations,id',
            'arrival_location_id' => 'required|exists:arrival_locations,id',
            'plant_id' => 'required|exists:plants,id',
            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('production_machines', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($this->arrival_location)
            ],
            'description' => 'nullable|string|max:500',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'company_location_id.required' => 'The Company Location ID is required.',
            'company_location_id.exists' => 'The selected Company Location does not exist.',
            'arrival_location_id.required' => 'The Arrival Location ID is required.',
            'arrival_location_id.exists' => 'The selected Arrival Location does not exist.',
            'plant_id.required' => 'The plant ID is required.',
            'plant_id.exists' => 'The selected plant does not exist.',
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number has already been taken for the selected company.',
            'name.required' => 'The Productin Machine name is required.',
            'name.unique' => 'The name has already been taken for the selected company.',
            'description.max' => 'The description must not exceed 500 characters.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
