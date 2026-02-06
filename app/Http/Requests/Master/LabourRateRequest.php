<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class LabourRateRequest extends FormRequest
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
            "rate" => "required",
            "bag_packing" => "required|numeric",
            "category_id" => "required|numeric",
            "factory_id" => "required|numeric",
            "company_id" => "required|numeric",
            "status" => "nullable|in:active,inactive",
            "description" => "nullable"
        ];
    }
}
