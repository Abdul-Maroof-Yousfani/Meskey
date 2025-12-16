<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SaleReturnRequest extends FormRequest
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
            "customer_id" => "required|numeric",
            "sr_no" => "required",
            "date" => "required|date",
            "reference_number" => "required",
            "contract_type" => "required|in:x-mill,pohanch",
            "company_location_id" => "required|numeric",
            "arrival_location_id" => "required|numeric",
            "storage_location_id" => "required|numeric",
            "remarks" => "nullable"
        ];
    }
}
