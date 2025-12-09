<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SalesInvoiceRequest extends FormRequest
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
            "customer_id" => ["required", "exists:customers,id"],
            "invoice_address" => ["nullable", "string"],
            "locations" => ["required", "exists:company_locations,id"],
            "arrival_locations" => ["required", "exists:arrival_locations,id"],
            "si_no" => ["required", "string"],
            "invoice_date" => ["required", "date"],
            "reference_number" => ["nullable", "string"],
            "sauda_type" => ["required", "in:pohanch,x-mill"],
            "remarks" => ["nullable", "string"],
            "dc_no" => ["nullable", "array"],
            "dc_no.*" => ["exists:delivery_challans,id"],
        ];
    }

    public function messages()
    {
        return [
            "customer_id.required" => "Customer is required",
            "customer_id.exists" => "Selected customer does not exist",
            "locations.required" => "Company Location is required",
            "locations.exists" => "Selected company location does not exist",
            "arrival_locations.required" => "Arrival Location is required",
            "arrival_locations.exists" => "Selected arrival location does not exist",
            "si_no.required" => "SI No is required",
            "invoice_date.required" => "Invoice Date is required",
            "sauda_type.required" => "Sauda Type is required",
            "sauda_type.in" => "Sauda Type must be either pohanch or x-mill",
        ];
    }
}

