<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QCRequest extends FormRequest
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
            "accepted_quantity" => "required",
            "rejected_quantity" => "required",
            "size" => "required",
            "bio" => "required",
            "smell" => "required",
            "printing" => "required",
            "bottom_stitching" => "required",
            "ready_to_pack" => "required",
            "remarks" => "required",
            "date" => "required"
        ];
    }
}
