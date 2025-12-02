<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SalesInquiryRequest extends FormRequest
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
            "reference_no" => "required",
            "locations" => "required|array",
            "inquiry_date" => "required|date",
            "customer" => "required",
            "contract_type" => "required|in:thadda,pohanch",
            "contact_person" => "required",
            "remarks" => "required",

            "item_id" => "nullable|array",
            "item_id.*" => "required",


            "qty" => "nullable|array",
            "qty.*" => "required",

            "rate" => "nullable|array",
            "rate.*" => "required",

            "desc" => "nullable|array",
            "desc.*" => "required",
        ];
    }

    public function messages() {
        return [
            "qty.*" => "Each Quantity is required",
            "rate.*" => "Each rate is required",
            "desc.*" => "Each Description is required",
            "item_id.*" => "Each item is required",
        ];
    }
}
