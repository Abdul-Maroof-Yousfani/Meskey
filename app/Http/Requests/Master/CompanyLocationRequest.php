<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyLocationRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('company_locations', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($this->company_location)
            ],
            'code' => [
                'required',
                'string',
                'max:255',

                Rule::unique('company_locations', 'code')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($this->company_location)
            ],
            'description' => 'nullable|string|max:500',
            'truck_no_format' => 'nullable|string|max:500',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number has already been taken for the selected company.',
            'name.required' => 'The Company Location name is required.',
            'name.unique' => 'The name has already been taken for the selected company.',
            'code.required' => 'The Company Location code is required.',
            'code.unique' => 'The code has already been taken for the selected company.',
            'description.max' => 'The description must not exceed 500 characters.',
            'truck_no_format.max' => 'The truck number format must not exceed 500 characters.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
