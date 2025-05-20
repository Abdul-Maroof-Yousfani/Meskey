<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class TicketContractRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            // 'supplier_id' => 'required|exists:suppliers,id',
            'contract_id' => 'required|exists:arrival_purchase_orders,id'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'Invalid Supplier.',
            'contract_id.required' => 'Contract is required.',
            'contract_id.exists' => 'Invalid Contract.'
        ];
    }
}
