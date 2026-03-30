<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionInputAnalysisRequest extends FormRequest
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
            'date' => 'required|date',
            'job_order_ids' => 'required|array',
            'brand_id' => 'required',
            'packing_id' => 'required',
            'location_id' => 'required',
            'variety' => 'required',
            'crop_year_id' => 'required',
            'items' => 'required|array',
            'items.*.time' => 'required',
            'items.*.params' => 'required|array',
        ];
    }
}
