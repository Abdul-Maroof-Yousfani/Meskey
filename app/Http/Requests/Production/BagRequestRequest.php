<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class BagRequestRequest extends FormRequest
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
            'request_date' => 'required|date',
            'gala_id' => 'required|exists:arrival_sub_locations,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:products,id',
            'items.*.brand_id' => 'nullable|exists:brands,id',
            'items.*.unit_id' => 'required|exists:unit_of_measures,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.*.item_id.required' => 'Item is required for all rows.',
            'items.*.unit_id.required' => 'Unit is required for all rows.',
            'items.*.quantity.required' => 'Quantity is required for all rows.',
            'items.*.quantity.min' => 'Quantity must be at least 0.01.',
        ];
    }
}
