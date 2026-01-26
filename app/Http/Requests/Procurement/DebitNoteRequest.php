<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class DebitNoteRequest extends FormRequest
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
            'grn_id' => 'required|exists:purchase_order_receivings,id',
            'bill_id' => 'required|exists:purchase_bills,id',
            'transaction_date' => 'required|date',
            'reference_number' => 'required',
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',
            'bill_data_id' => 'required|array|min:1',
            'bill_data_id.*' => 'required|exists:purchase_bills_data,id',
            'grn_qty' => 'required|array|min:1',
            'grn_qty.*' => 'required|numeric|min:0',
            'debit_note_quantity' => 'required|array|min:1',
            'debit_note_quantity.*' => 'required|numeric|min:0',
            'rate' => 'required|array|min:1',
            'rate.*' => 'required|numeric|min:0',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0',
        ];
    }
}
