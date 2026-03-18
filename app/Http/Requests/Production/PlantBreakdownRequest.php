<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlantBreakdownRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Get the breakdown ID for update case (if exists)
        $breakdownId = $this->route('plant_breakdown') ? $this->route('plant_breakdown') : null;
// dd($breakdownId);
        return [
            'company_id' => 'required|exists:companies,id',
            'date' => [
                'required',
                'date',
                Rule::unique('plant_breakdowns')->where(function ($query) {
                    return $query->where('plant_id', $this->plant_id)
                                 ->where('company_id', $this->company_id)
                                 ->where('date', $this->date);
                })->ignore($breakdownId)
            ],
            'plant_id' => 'required|exists:plants,id',
            'production_voucher_id' => 'nullable|exists:production_vouchers,id',
            // 'user_id' => 'required|exists:users,id',
            'breakdown_type_id' => 'required|array|min:1',
            'breakdown_type_id.*' => 'required|exists:plant_breakdown_types,id',
            'from' => 'required|array|min:1',
            'from.*' => 'required',
            'to' => 'required|array|min:1',
            'to.*' => 'required',
            'hours' => 'nullable|array',
            'hours.*' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|array',
            'remarks.*' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'date.required' => 'The date field is required.',
            'date.date' => 'The date must be a valid date.',
            'date.unique' => 'A breakdown record already exists for this plant on the selected date.',
            'plant_id.required' => 'The plant field is required.',
            'plant_id.exists' => 'The selected plant does not exist.',
            'production_voucher_id.exists' => 'The selected production voucher does not exist.',
            // 'user_id.required' => 'The user ID is required.',
            // 'user_id.exists' => 'The selected user does not exist.',
            'breakdown_type_id.required' => 'At least one breakdown item is required.',
            'breakdown_type_id.array' => 'Breakdown items must be an array.',
            'breakdown_type_id.min' => 'At least one breakdown item is required.',
            'breakdown_type_id.*.required' => 'Breakdown type is required for each item.',
            'breakdown_type_id.*.exists' => 'One or more selected breakdown types do not exist.',
            'from.required' => 'From time is required for each item.',
            'from.array' => 'From times must be an array.',
            'from.min' => 'At least one from time is required.',
            'from.*.required' => 'From time is required for each item.',
            'to.required' => 'To time is required for each item.',
            'to.array' => 'To times must be an array.',
            'to.min' => 'At least one to time is required.',
            'to.*.required' => 'To time is required for each item.',
            'hours.*.numeric' => 'Hours must be a number.',
            'hours.*.min' => 'Hours must be greater than or equal to 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Convert date to proper format if needed
     */
    protected function prepareForValidation()
    {
        // Ensure date is in proper format
        if ($this->date) {
            $this->merge([
                'date' => \Carbon\Carbon::parse($this->date)->format('Y-m-d'),
            ]);
        }
    }
}