<?php

namespace App\Http\Requests\Arrival;

use Illuminate\Foundation\Http\FormRequest;

class InnerSamplingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id' => 'required|exists:arrival_tickets,id',
            'company_id' => 'required|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_id.required' => 'Ticket ID is required',
            'ticket_id.exists' => 'The selected ticket does not exist',
            'company_id.required' => 'Company ID is required',
            'company_id.exists' => 'The selected company does not exist',
        ];
    }
}
