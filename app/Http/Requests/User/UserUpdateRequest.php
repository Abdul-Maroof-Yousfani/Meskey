<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Change this if authorization logic is needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user'); // Assuming route model binding is used

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'password' => 'nullable|same:confirm-password',
            'role' => 'required|array',
            'role.*' => 'exists:roles,id',
            'company' => 'required|array',
            'company.*' => 'exists:companies,id',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'This email is already in use.',
            'password.same' => 'The password and confirmation password must match.',
            'role.required' => 'At least one role is required.',
            'role.*.exists' => 'One or more selected roles are invalid.',
            'company.required' => 'At least one company is required.',
            'company.*.exists' => 'One or more selected companies are invalid.',
        ];
    }
}
