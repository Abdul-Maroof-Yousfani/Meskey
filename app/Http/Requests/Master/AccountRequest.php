<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 'unique_no' => [
            //     'required',
            //     'string',
            //     'max:50',
            //     Rule::unique('accounts', 'unique_no')
            //         ->ignore($this->account)
            // ],
            
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'account_type' => ['required', Rule::in(['debit', 'credit'])],
            'parent_id' => 'nullable|exists:accounts,id',
            'parent_unique_no' => 'nullable|string|max:50',
            'is_operational' => ['required', Rule::in(['yes', 'no'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id'
        ];
    }

    public function messages()
    {
        return [
            'unique_no.required' => 'The account code is required.',
            'unique_no.unique' => 'This account code already exists.',
            'name.required' => 'The account name is required.',
            'account_type.required' => 'The account type is required.',
            'account_type.in' => 'Account type must be either debit or credit.',
            'parent_id.exists' => 'The selected parent account does not exist.',
            'is_operational.required' => 'Please specify if the account is operational.',
            'status.required' => 'The status field is required.',
        ];
    }
}
