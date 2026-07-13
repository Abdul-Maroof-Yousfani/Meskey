<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unique_no' => ['required', 'string'],
            'rv_date' => ['required', 'date'],
            'voucher_type' => ['required', 'in:bank_payment_voucher,cash_payment_voucher'],
            'account_id' => ['required', 'exists:accounts,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'ref_bill_no' => ['nullable', 'string'],
            'bill_date' => ['nullable', 'date'],
            'cheque_no' => ['nullable', 'string'],
            'cheque_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.reference_id' => ['required', 'integer'],
            'items.*.reference_type' => ['required', 'in:sale_order,sales_invoice,advance'],
            'items.*.amount' => ['nullable', 'numeric'],
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.tax_amount' => ['nullable', 'numeric'],
            'items.*.net_amount' => ['nullable', 'numeric'],
            'items.*.line_desc' => ['nullable', 'string'],
            'bank_details' => ['nullable', 'array'],
            'bank_details.*.account_id' => ['required_without:bank_details.*.customer_advance_id', 'nullable', 'exists:accounts,id'],
            'bank_details.*.customer_advance_id' => ['required_without:bank_details.*.account_id', 'nullable', 'exists:customer_advances,id'],
            'bank_details.*.amount' => ['required', 'numeric', 'min:0'],
            'bank_details.*.cheque_no' => ['nullable', 'string'],
            "company_id" => ["required"]
        ];
    }
}


