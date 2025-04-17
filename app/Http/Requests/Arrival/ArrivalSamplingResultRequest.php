<?php

namespace App\Http\Requests\Arrival;

use Illuminate\Foundation\Http\FormRequest;

class ArrivalSamplingResultRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'arrival_product_id' => 'required|exists:products,id',
            'arrival_sampling_request_id' => 'required|exists:arrival_sampling_requests,id',
            'product_slab_type_id' => 'required|array',
            'product_slab_type_id.*' => 'required|exists:product_slab_types,id',
            'checklist_value' => 'nullable|array',
            'checklist_value.*' => 'string',
            'remark' => 'nullable|string',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Company ID is required.',
            'company_id.exists' => 'Invalid Company ID.',
            'arrival_sampling_request_id.required' => 'Arrival Sampling Request ID is required.',
            'arrival_sampling_request_id.exists' => 'Invalid Arrival Sampling Request ID.',
            'product_slab_type_id.required' => 'At least one Product Slab Type is required.',
            'product_slab_type_id.array' => 'Product Slab Type must be an array.',
            'product_slab_type_id.*.exists' => 'Invalid Product Slab Type ID.',
            'checklist_value.array' => 'Checklist value must be an array.',
            'checklist_value.*.string' => 'Each checklist value must be a string.',
            'remark.string' => 'Remark must be a string.',
        ];
    }
}
