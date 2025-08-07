<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class AdvancePaymentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'loading_type' => 'required|in:loading,without_loading',
            'supplier_name' => 'required|string|max:255',
            'contract_no' => 'required|string|max:255',
            'contract_rate' => 'required|numeric|min:0',
            'min_contract_range' => 'required|numeric|min:0',
            'max_contract_range' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'remaining_amount' => 'required|numeric',
            'payment_request_amount' => 'required|numeric|min:0',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'loading_type.required' => 'Loading type is required.',
            'loading_type.in' => 'Loading type must be either loading or without_loading.',
            'supplier_name.required' => 'Supplier name is required.',
            'supplier_name.string' => 'Supplier name must be a string.',
            'supplier_name.max' => 'Supplier name cannot exceed 255 characters.',
            'contract_no.required' => 'Contract number is required.',
            'contract_no.string' => 'Contract number must be a string.',
            'contract_no.max' => 'Contract number cannot exceed 255 characters.',
            'contract_rate.required' => 'Contract rate is required.',
            'contract_rate.numeric' => 'Contract rate must be a number.',
            'contract_rate.min' => 'Contract rate must be at least 0.',

            'total_amount.required' => 'Total amount is required.',
            'total_amount.numeric' => 'Total amount must be a number.',
            'total_amount.min' => 'Total amount must be at least 0.',
            'paid_amount.required' => 'Paid amount is required.',
            'paid_amount.numeric' => 'Paid amount must be a number.',
            'paid_amount.min' => 'Paid amount must be at least 0.',
            'remaining_amount.required' => 'Remaining amount is required.',
            'remaining_amount.numeric' => 'Remaining amount must be a number.',
            'payment_request_amount.required' => 'Payment request amount is required.',
            'payment_request_amount.numeric' => 'Payment request amount must be a number.',
            'payment_request_amount.min' => 'Payment request amount must be at least 0.',

        ];
    }
}
