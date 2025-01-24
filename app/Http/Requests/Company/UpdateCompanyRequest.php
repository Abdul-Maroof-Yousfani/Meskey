<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        $companyId = $this->route('company'); // Fetching the company from route parameter

        // If $companyId is an object, use its ID, otherwise use null
        $companyId = is_object($companyId) ? $companyId->id : $companyId;
        return [
            'name' => 'required|string|max:255|unique:companies,name,' . $companyId,

            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'connection_database' => 'nullable|string|max:255',
            'ntn' => 'nullable|string|max:255',
            'stn' => 'nullable|string|max:255',
            'app_key' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The company name is required.',
            'name.unique' => 'This company name has already been taken.',
            'email.email' => 'Please provide a valid email address.',
            'phone.max' => 'The phone number cannot exceed 20 characters.',
            'logo.image' => 'The logo must be an image file.',
            'logo.mimes' => 'The logo must be a file of type: jpg, jpeg, png, gif.',
            'logo.max' => 'The logo file cannot be larger than 2MB.',
            'status.in' => 'The status must be either 1 or 2.',
        ];
    }
}
