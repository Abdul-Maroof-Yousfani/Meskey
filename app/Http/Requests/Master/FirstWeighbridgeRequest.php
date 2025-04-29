<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class FirstWeighbridgeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'first_weight' => 'required|numeric',
            'remark' => 'nullable|string',
        ];
    }
}
