<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionInputAnalysisRequest extends FormRequest
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
                $arrivalLocId = $this->arrival_location_id ?: $req->arrival_location_id;
                $plantId = $this->plant_id ?: $req->plant_id;
                $date = $this->date ?: now()->format('Y-m-d');
                $reqDate = $req->request_date?->format('Y-m-d');
                $excludeId = $this->route('production_input_analysis') ?: $this->route('id');

                $firstProduct = \App\Models\Production\ProductionAnalysis::getFirstProductForRun(
                    $arrivalLocId,
                    $plantId,
                    $date,
                    $reqDate,
                    $excludeId,
                    'analysis'
                );

                $resolvedProductId = $firstProduct ? $firstProduct->id : $this->product_id;

                $this->merge([
                    'location_id' => $this->location_id ?: $req->company_location_id,
                    'arrival_location_id' => $arrivalLocId,
                    'plant_id' => $plantId,
                    'product_id' => $resolvedProductId ?: $this->product_id,
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
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'location_id' => 'required',
            'arrival_location_id' => 'required',
            'plant_id' => 'required',
            'items' => 'required|array',
            'items.*.time' => 'required',
            'items.*.params' => 'required|array',
        ];
    }
}
