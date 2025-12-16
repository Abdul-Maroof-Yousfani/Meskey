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
        return [
            "dc_no" => [ "required" ],
            "date" => [ "required", "date" ],
            "customer_id" => [ "required", "numeric" ],
            "reference_number" => [ "required" ],
            "labour" => [ "required" ],
            "labour_amount" => [ "required" ],
            "transporter" => [ "required" ],
            "transporter_amount" => [ "required" ],
            "weighbridge" => [ "required" ],  
            "weighbridge_amount" => [ "required" ],
            "sauda_type" => [ "required" ],
            "remarks" => [ "required" ],

            "truck_no" => ["required"],
            "truck_no.*" => ["required"],

            "bilty_no" => ["required"],
            "bilty_no.*" => ["required"],
        ];
    }
}
