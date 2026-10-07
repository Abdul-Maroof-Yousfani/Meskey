<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionMachineAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('analysis_request_id')) {
            $req = \App\Models\Production\ProductionAnalysisRequest::find($this->analysis_request_id);
            if ($req) {
                $this->merge([
                    'company_location_id' => $this->company_location_id ?: $req->company_location_id,
                    'arrival_location_id' => $this->arrival_location_id ?: $req->arrival_location_id,
                    'plant_id' => $this->plant_id ?: $req->plant_id,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'analysis_request_id' => 'required|exists:production_analysis_requests,id',
            'date' => 'required|date',
            'company_location_id' => 'required|exists:company_locations,id',
            'arrival_location_id' => 'required|exists:arrival_locations,id',
            'plant_id' => 'required|exists:plants,id',
            'production_machine_id' => 'required|exists:production_machines,id',
            'items' => 'required|array|min:1',
            'items.*.time' => 'required',
            'items.*.unit_id' => 'nullable|exists:unit_of_measures,id',
            'items.*.params' => 'nullable|array'
        ];
    }
}
