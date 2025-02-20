<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class BrokerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to true if the user is authorized to make this request
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id', // Ensure company exists in the 'companies' table
            'unique_no' => 'nullable|string|max:255|unique:brokers,unique_no',
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'ntn' => 'nullable|string|max:15',
            'stn' => 'nullable|string|max:15',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number must be unique.',
            'name.required' => 'The name is required.',
            'prefix.string' => 'The prefix must be a valid string.',
            'email.email' => 'Please provide a valid email address.',
            'phone.string' => 'The phone number must be a valid string.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}