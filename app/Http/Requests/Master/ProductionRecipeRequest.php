<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class ProductionRecipeRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'commodity_id' => 'required|exists:products,id',
            'crop_year_id' => 'nullable|exists:crop_years,id',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|integer',
            'items.*.key' => 'nullable|string|max:191',
            'items.*.value' => 'nullable|string|max:500',
            'items.*.type' => 'nullable|string|max:50',
            'parameters' => 'nullable|array',
            'parameters.*.id' => 'nullable|integer',
            'parameters.*.key' => 'nullable|string|max:191',
            'parameters.*.value' => 'nullable|string|max:500',
            'parameters.*.type' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The Production Recipe Name is required.',
            'commodity_id.required' => 'Please select a Commodity.',
            'commodity_id.exists' => 'The selected Commodity is invalid.',
            'crop_year_id.exists' => 'The selected Crop Year is invalid.',
            'status.required' => 'The status is required.',
        ];
    }
}
