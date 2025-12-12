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
            "order_date" => "nullable|date",
            "reference_no" => "required",
            "so_reference_no" => "nullable|string|max:255",
            "customer_id" => "required|numeric",
            "inquiry_id" => "nullable|numeric",
            "sauda_type" => "required|in:pohanch,x-mill,thadda",
            "payment_term_id" => "required|numeric",
            "company_id" => "required",
            'pay_type_id' => 'required',
            'token_money' => 'required|numeric',
            "remarks" => "nullable",
            "contact_person" => "nullable|string|max:255",
            "arrival_location_id" => "nullable|array",
            "arrival_location_id.*" => "integer|exists:arrival_locations,id",
            "arrival_sub_location_id" => "nullable|array",
            "arrival_sub_location_id.*" => "integer|exists:arrival_sub_locations,id",

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

            "sales_inquiry_id" => "nullable",
            "sales_inquiry_id.*" => "nullable",
            
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
