<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClearingAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clearingAgent = $this->route('clearing_agent');

        return [
            'company_id' => 'required|exists:companies,id',
            'unique_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('clearing_agents', 'unique_no')->ignore($clearingAgent),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clearing_agents', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($clearingAgent),
            ],
            'rate' => 'required|numeric|min:0',
            'owner_name' => 'required|string|max:255',
            'owner_mobile_no' => 'required|string|max:11|regex:/^[0-9]{11}$/',
            'owner_cnic_no' => 'required|string|regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/',
            'next_to_kin' => 'nullable|string|max:255',
            'next_to_kin_mobile_no' => 'nullable|string|max:11|regex:/^[0-9]{11}$/',
            'owner_bank_detail' => 'nullable|string|max:255',
            'company_bank_detail' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'ntn' => 'nullable|string|max:15',
            'stn' => 'nullable|string|max:15',
            'attachment' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_mobile_no.regex' => 'Mobile number must be exactly 11 digits.',
            'next_to_kin_mobile_no.regex' => 'Mobile number must be exactly 11 digits.',
            'owner_cnic_no.regex' => 'CNIC must be in format: 12345-1234567-1',
            'company_id.required' => 'The company is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'unique_no.unique' => 'The unique number must be unique.',
            'name.required' => 'The clearing agent name is required.',
            'rate.required' => 'The rate is required.',
            'owner_name.required' => 'The owner name is required.',
            'owner_mobile_no.required' => 'The owner mobile number is required.',
            'status.required' => 'The status is required.',
        ];
    }
}
