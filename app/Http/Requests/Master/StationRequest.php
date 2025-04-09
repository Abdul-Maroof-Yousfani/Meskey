<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
 return [
            'company_id' => 'required|exists:companies,id',
            'name' => [
                'required',
                'string',
                'max:255',
  
                       Rule::unique('arrival_truck_types', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($this->truck_type)
            ],
            'description' => 'nullable|string|max:500',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'name.required' => 'The name field is required.',
            'name.unique' => 'The name has already been taken for the selected company.',
        ];
    }
}
