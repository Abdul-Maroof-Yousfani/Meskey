<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryChallanRequest extends FormRequest
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
        $rules = [
            "dc_no" => ["required"],
            "date" => ["required", "date"],
            "customer_id" => ["required", "numeric"],
            "labour_rate" => ["required"],
        ];

        if (request()->type === 'sale_delivery_challan') {
            $rules['sauda_type'] = ['required'];
        } else {
            $rules['sauda_type'] = ['nullable'];
        }

        return $rules;

        // return [
        //     "dc_no" => [ "required" ],
        //     "date" => [ "required", "date" ],
        //     "customer_id" => [ "required", "numeric" ],
        //     // "reference_number" => [ "required" ],
        //     // "labour" => [ "required" ],
        //     // "labour_amount" => [ "required" ],
        //     // "transporter" => [ "required" ],
        //     // "transporter_amount" => [ "required" ],
        //     // "weighbridge" => [ "required" ],  
        //     // "weighbridge_amount" => [ "required" ],
        //     "sauda_type" => [ "required" ],
        //     "labour_rate" => [ "required" ],
        //     // "remarks" => [ "required" ],

        //     // "truck_no" => ["required"],
        //     // "truck_no.*" => ["required"],

        //     // "bilty_no" => ["required"],
        //     // "bilty_no.*" => ["required"],
        // ];
    }
}
