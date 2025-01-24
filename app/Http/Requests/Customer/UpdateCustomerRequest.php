<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
        $customerId = $this->route('customer'); // Assuming you are passing customer ID in route

        return [
            'name' => 'required|string|max:100',
            'phone_no' => "required|string|unique:customers,phone_no,$customerId,id,deleted_at,NULL|max:100",
            'whats_app_no' => 'nullable|string|max:100',
            'flat_shop_number' => 'nullable|string|max:100',
            'building_no' => 'nullable|string|max:100',
            'road_no' => 'nullable|string|max:100',
            'block_no' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ];
    }
}
