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
            'items.*.reference_type' => ['required', 'in:sale_order,sales_invoice'],
            'items.*.amount' => ['nullable', 'numeric'],
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.tax_amount' => ['nullable', 'numeric'],
            'items.*.net_amount' => ['nullable', 'numeric'],
            'items.*.line_desc' => ['nullable', 'string'],
        ];
    }
}


