<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize()
    {
        // You can add your authorization logic here
        return true; 
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name', 
            'permission' => 'required|array', 
           // 'permission.*' => 'exists:permissions,id', 
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The role name is required.',
            'name.unique' => 'This role name already exists.',
            'permission.required' => 'At least one permission is required.',
            'permission.array' => 'Permissions must be provided as an array.',
            'permission.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }
}
