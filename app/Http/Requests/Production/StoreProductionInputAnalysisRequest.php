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
                // Input product is chosen freely, so it is not replaced here
                $this->merge([
                    'location_id' => $this->location_id ?: $req->company_location_id,
                    'arrival_location_id' => $arrivalLocId,
                    'plant_id' => $plantId,
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
            'job_order_id' => 'required|exists:job_orders,id',
            'job_orders' => 'nullable|array',
            'job_orders.*' => 'nullable|exists:job_orders,id',
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'location_id' => 'required',
            'arrival_location_id' => 'required',
            'plant_id' => 'required',
            'items' => 'required|array',
            'items.*.time' => 'required',
            'items.*.params' => 'required|array',
            'items.*.params.*' => 'required|numeric|min:0',
            // Other Products: a row needs both product and percent (fully empty rows are skipped)
            'other_products' => 'nullable|array',
            'other_products.*.product_id' => 'nullable|required_with:other_products.*.percentage|exists:products,id',
            'other_products.*.percentage' => 'nullable|required_with:other_products.*.product_id|numeric|min:0|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $total = collect($this->input('other_products', []))->sum(fn($row) => (float) ($row['percentage'] ?? 0));
            if ($total > 100) {
                $validator->errors()->add('other_products', 'Other Products ka total percent 100 se zyada nahi ho sakta.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'job_order_id.required' => 'Job Order is required.',
            'items.*.params.*.required' => 'All QC Checklist values are required (enter 0 if none).',
            'items.*.params.*.numeric' => 'QC Checklist values must be numbers.',
            'items.*.params.*.min' => 'QC Checklist values cannot be less than 0.',
            'other_products.*.product_id.required_with' => 'Other Products: select a product for the entered percent.',
            'other_products.*.product_id.exists' => 'Other Products: selected product is invalid.',
            'other_products.*.percentage.required_with' => 'Other Products: enter percent for the selected product.',
            'other_products.*.percentage.numeric' => 'Other Products: percent must be a number.',
            'other_products.*.percentage.min' => 'Other Products: percent cannot be less than 0.',
            'other_products.*.percentage.max' => 'Other Products: percent cannot be more than 100.',
        ];
    }
}
