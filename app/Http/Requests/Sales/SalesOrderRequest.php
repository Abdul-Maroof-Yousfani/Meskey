<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SalesOrderRequest extends FormRequest
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
            "delivery_date" => "required|date",
            "expiry_date" => "required|date",
            "reference_no" => "required",
            "customer_id" => "required|numeric",
            "inquiry_id" => "nullable|numeric",
            "sauda_type" => "required|in:pohanch,x-mill",
            "payment_term_id" => "required|numeric",
            "company_id" => "required",
            'pay_type_id' => 'required',

            "item_id" => "required",
            "item_id.*" => "required",

            "qty" => "required",
            "qty.*" => "required",

            "rate" => "required",
            "rate.*" => "required",

            "brand_id" => "required",
            "brand_id.*" => "required",

            "pack_size" => "required",
            "pack_size.*" => "required",

            "sales_inquiry_id" => "required",
            "sales_inquiry_id.*" => "required"
        ];
    }

    public function messages() {
        return [
            "item_id.required" => "Each item is required",
            "qty.required" => "Each quantity is required",
            "rate.required" => "Each rate is required",
            'pay_type_id' => "Pay Type is required"
        ];
    }
}
