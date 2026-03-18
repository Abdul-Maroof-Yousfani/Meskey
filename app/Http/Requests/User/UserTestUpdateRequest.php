<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserTestUpdateRequest extends FormRequest
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
        $userId = $this->route('user'); // Assuming route model binding is used

        return [
            'name' => 'required|string|max:255',
            // 'username' => 'required|unique:users,username',
            // 'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|confirmed',
            'parent_user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'password.same' => 'The password and confirmation password must match.',
        ];
    }
}
