<?php

namespace App\Http\Requests\Procurement\Store;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseBillRequest extends FormRequest
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
            'supplier_id' => "required",
            'purchase_bill_date' => "required",
            'company_location' => 'required',
            "grn_no" => "required",
            "reference_no" => "required",
            "description" => "required",
            // "description.*" => "required",
            "qty.*" => "required",
            "rate.*" => "required",
            "gross_amount.*" => "required",
            'tax_percent.*' => "required",
            "tax_amount.*" => "required",
            "deduction_per_piece.*" => "required",
            "net_amount.*" => "required",
            "discount_id.*" => "required",
            "discount_amount.*" => "required",
            "deduction.*" => "required",
            "final_amount.*" => "required",
        ];
    }

    public function messages() {
        return [
            "description.*.required" => "Each description is required",
            "qty.*.required" => "Each quantity is required",
            "rate.*.required" => "Each rate is required",
            "gross_amount.*.required" => "Each gross amount is required",
            "tax_percent.*.required" => "Each Tax is required",
            "net_amount.*.required" => "Each net amount is required",
            "discount_id.*.required" => "Each discount percentage is required",
            "discount_amount.*.required" => "Each discount amount is required",
            "deduction.*.required" => "Each deduction is required",
            "final_amount.*.required" => "Each final amount is required",
        ];
    }
}
