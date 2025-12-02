<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayTypeRequest extends FormRequest
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
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "status" => ["required", "in:active,inactive"]
        ];
    }

    public function messages()
    {
        return [
            "name.required" => "Pay Type name is required",
            "status.required" => "Status is required",
            "status.in" => "Status must be either active or inactive"
        ];
    }
}

