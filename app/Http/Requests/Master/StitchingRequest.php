<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StitchingRequest extends FormRequest
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
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('stitchings', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($this->stitching)
            ],
            'description' => 'nullable|string|max:500',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
