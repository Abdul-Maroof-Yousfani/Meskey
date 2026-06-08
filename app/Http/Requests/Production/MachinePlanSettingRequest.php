<?php
// app/Http/Requests/Production/MachinePlanSettingRequest.php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class MachinePlanSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'date' => 'required|date',
            'plant_id' => 'required|exists:plants,id',
            'production_voucher_id' => 'nullable|exists:production_vouchers,id',
            'remarks' => 'nullable|string',
            'machines' => 'nullable|array',
            'machines.*.production_machine_id' => 'required|exists:production_machines,id',
            'machines.*.hours' => 'nullable|numeric|min:0|max:24',
            'machines.*.is_enabled' => 'boolean',
            'machines.*.remarks' => 'nullable|string',
        ];
    }
}