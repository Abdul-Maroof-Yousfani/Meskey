<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Set to true if all users can access this request
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'business_logo' => 'required|image',
            'business_name' => 'required|string|max:255|unique:businesses,business_name',
            'registration_no' => 'required|string|unique:businesses,registration_no',
            'address' => 'required|string|max:255',
            'phone_no' => 'required|string|max:15|unique:businesses,phone_no',
            'whats_app_no' => 'nullable|string|max:15|unique:businesses,whats_app_no',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'photos.*' => 'nullable', 
        ];
    }

    /**
     * Customize the error messages for validation.
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'The password confirmation does not match.',
            'email.unique' => 'The email is already registered.',
        ];
    }
}
