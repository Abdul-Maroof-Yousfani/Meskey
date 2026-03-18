<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class DirectReceiptVoucherRequest extends FormRequest
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
            "voucher_type" => "required|in:bank_payment_voucher,cash_payment_voucher",
            "rv_date" => "required|date",
            "unique_no" => "required",
            "account_id" => "required|numeric",
            "ref_bill_no" => "required",
            "bill_date" => "required|date",
            "company_id" => "required",

            "account" => "required",
            "account.*" => "required",

            "amount" => "required",
            "amount.*" => "required",

            // "tax_id" => "required",
            // "tax_id.*" => "required|numeric",

            // "tax_amount" => "required",
            // "tax_amount.*" => "required",

            "net_amount" => "required",
            "net_amount.*" => "required",

            // "description" => "required",
            // "description.*" => "required",
        ];
    }
}
