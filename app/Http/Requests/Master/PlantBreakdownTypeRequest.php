<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlantBreakdownTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $plantBreakdownTypeId = $this->route('plant_breakdown_type');
        $plantBreakdownTypeId = is_object($plantBreakdownTypeId) ? $plantBreakdownTypeId->id : $plantBreakdownTypeId;

        return [
            'company_id' => 'required|exists:companies,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plant_breakdown_types', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($plantBreakdownTypeId)
            ],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'name.required' => 'The name field is required.',
            'name.unique' => 'The name has already been taken for the selected company.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
