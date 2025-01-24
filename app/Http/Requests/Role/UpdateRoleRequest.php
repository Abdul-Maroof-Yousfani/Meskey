<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;


class UpdateRoleRequest extends FormRequest
{
    public function authorize()
    {
        // You can add your authorization logic here
        return true; 
    }

    public function rules()
    {
        $roleId = $this->route('role'); // Assuming role ID is in the route

        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $roleId, // Allow updating role name with the current ID
            'permission' => 'required|array', // Permissions must be an array
    //        'permission.*' => 'exists:permissions,id', // Ensure all permissions exist
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
