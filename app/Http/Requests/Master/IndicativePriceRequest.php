<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class IndicativePriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Or implement your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:company_locations,id',
            'type_id' => 'required|exists:sauda_types,id',
            'crop_year' => 'required|integer|digits:4',
            'delivery_condition' => 'required|string|max:255',
            'cash_rate' => 'required|numeric|min:0',
            'cash_days' => 'required|integer|min:0',
            'credit_rate' => 'required|numeric|min:0',
            'credit_days' => 'required|integer|min:0',
            'others' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'product_id.required' => 'Please select a commodity',
            'location_id.required' => 'Please select a location',
            'type_id.required' => 'Please select a type',
            'crop_year.required' => 'Please select a crop year',
            'delivery_condition.required' => 'Delivery condition is required',
        ];
    }
}
