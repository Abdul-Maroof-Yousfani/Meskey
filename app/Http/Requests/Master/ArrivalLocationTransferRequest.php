<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class ArrivalLocationTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'arrival_location_id' => 'required|exists:arrival_locations,id',
            'remark' => 'nullable|string',
        ];
    }
}
