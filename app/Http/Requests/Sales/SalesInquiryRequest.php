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

            "inquiry_date" => "required|date|before_or_equal:required_date",
            "customer" => "required",
            "reference_number" => "nullable",

            "required_date" => "required|date|after_or_equal:inquiry_date",
            "arrival_location_id" => "nullable|array|min:1",
            "arrival_location_id.*" => "integer|exists:arrival_locations,id",
            "arrival_sub_location_id" => "nullable|array|min:1",
            "arrival_sub_location_id.*" => "integer|exists:arrival_sub_locations,id",

            "contract_type" => "required|in:pohanch,x-mill",
            "contact_person" => "nullable",
            "remarks" => "nullable",

            "item_id" => "nullable|array",
            "item_id.*" => "required",


            // "qty" => "nullable|array",
            // "qty.*" => "required",


            "qty" => "required",
            "qty.*" => "required",

            "minimum_qty" => "nullable|array",
            "minimum_qty.*" => "nullable|numeric|gt:0",

            "rate" => "nullable|array",
            "rate.*" => "required",

            // "desc" => "nullable|array",
            // "desc.*" => "nullable|required",

            "bag_size" => "nullable|array",
            "bag_size.*" => "required",

            "no_of_bags" => "nullable|array",
            "no_of_bags.*" => "required",

            "bag_type" => "nullable|array",
            "bag_type.*" => "required",

            "brand_id" => "nullable|array",
            "brand_id.*" => "required",

            "pack_size" => "nullable|array",
            "pack_size.*" => "required",

            "token_money" => "nullable|numeric",

            "rate_per_mond" => "required",
            "rate_per_mond.*" => "required"

        ];
    }



    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $qtys = $this->input('qty', []);
            $minimumQtys = $this->input('minimum_qty', []);

            foreach ($minimumQtys as $index => $minimumQty) {
                $qty = $qtys[$index] ?? null;

                if ($minimumQty !== null && $minimumQty >= $qty) {
                    $validator->errors()->add(
                        "minimum_qty.$index",
                        "The minimum_qty.$index must be less than quantity."
                    );
                }
            }
        });
    }
    public function messages()
    {
        return [
            "qty.*" => "Each Quantity is required",
            "rate.*" => "Each rate is required",
            "desc.*" => "Each Description is required",
            "item_id.*" => "Each item is required",
        ];
    }
}
