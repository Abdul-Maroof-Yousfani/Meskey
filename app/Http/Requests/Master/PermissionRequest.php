<?php

namespace App\Http\Requests\Master;

use App\Models\Master\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => $this->filled('guard_name') ? trim($this->guard_name) : 'web',
            'parent_id' => $this->filled('parent_id') ? (int) $this->parent_id : null,
            'name' => trim($this->name ?? ''),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $permissionId = $this->route('permission') ?? $this->route('id');
        $guardName = $this->input('guard_name', 'web');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where(fn($query) => $query->where('guard_name', $guardName))
                    ->ignore($permissionId),
            ],
            'guard_name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'exists:permissions,id',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];

        // Additional validation when updating to prevent selecting self or descendants as parent
        if ($permissionId) {
            $descendants = Permission::getDescendantIds($permissionId);
            $disallowedParents = array_merge([ (int) $permissionId ], $descendants);

            $rules['parent_id'][] = Rule::notIn($disallowedParents);
        }

        return $rules;
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required.',
            'name.unique' => 'A permission with this name and guard already exists.',
            'parent_id.exists' => 'The selected parent permission is invalid.',
            'parent_id.not_in' => 'A permission cannot have itself or one of its child permissions as a parent.',
        ];
    }
}
