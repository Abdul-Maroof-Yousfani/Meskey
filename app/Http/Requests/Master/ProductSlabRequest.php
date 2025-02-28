<?php

namespace App\Http\Requests\Master;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Master\ProductSlab;

class ProductSlabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'product_id' => ['required', 'exists:products,id'],
            'product_slab_type_id' => ['required', 'exists:product_slab_types,id'],
            'from' => ['required', 'numeric'],
            'to' => ['required', 'numeric', 'gt:from'],
            'deduction_type' => ['required', 'string', 'in:kg,amount'],
            'deduction_value' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('from') && $this->has('to') && $this->has('product_id')) {
                $exists = ProductSlab::where('company_id', $this->company_id)
                    ->where('product_slab_type_id', $this->product_slab_type_id)
                    ->where('product_id', $this->product_id)
                    ->where('from', $this->from)
                    ->where('to', $this->to)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('product_id', 'The given product slab range already exists.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Company ID is required.',
            'company_id.exists' => 'Selected company does not exist.',
            'product_id.exists' => 'Selected product does not exist.',
            'product_slab_type_id.exists' => 'Selected slab type does not exist.',
            'from.numeric' => 'The "From" value must be a number.',
            'to.numeric' => 'The "To" value must be a number.',
            'to.gt' => 'The "To" value must be greater than "From".',
            'deduction_type.in' => 'Deduction type must be either percentage or fixed.',
            'deduction_value.numeric' => 'Deduction value must be a number.',
            'deduction_value.min' => 'Deduction value must be at least 0.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status value.',
            'business_id.required' => 'Business ID is required.',
            'business_id.exists' => 'The selected business does not exist.',
        ];
    }
}
