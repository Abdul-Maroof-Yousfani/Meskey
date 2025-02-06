<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitOfMeasureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $unit_of_measureId = $this->route('unit_of_measure');
        $unit_of_measureId = is_object($unit_of_measureId) ? $unit_of_measureId->id : $unit_of_measureId;

        return [
            'company_id' => 'required|exists:companies,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measures', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($unit_of_measureId)
            ],
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
