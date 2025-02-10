<?php

namespace App\Http\Requests\Arrival;

use Illuminate\Foundation\Http\FormRequest;

class ArrivalTicketRequest extends FormRequest
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
     */public function rules(): array
    {
        return [
            'unique_no' => 'nullable|string|max:255|unique:arrival_tickets,unique_no,NULL,id,company_id,' . $this->company_id,
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'supplier_name' => 'required|string|max:255',
            'truck_no' => 'required|string|max:255',
            'bilty_no' => 'required|string|max:255',
            'loading_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number must be unique for the selected company.',
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'truck_no.required' => 'The truck number is required.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
