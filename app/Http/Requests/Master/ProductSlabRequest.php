<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSlabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productSlabId = $this->route('product_slab');

        return [
            'company_id' => 'required|exists:companies,id',
            'unique_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product_slabs', 'unique_no')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($productSlabId)
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_slabs', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($productSlabId)
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
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number has already been taken for the selected company.',
            'name.required' => 'The product slab name is required.',
            'name.unique' => 'The name has already been taken for the selected company.',
            'description.max' => 'The description must not exceed 500 characters.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
