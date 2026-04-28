<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Info
            'export_soda_id'       => ['nullable', 'exists:export_soda_fields,id'],
            'buyer_id'             => ['required', 'exists:customers,id'],
            'company_id'           => ['required', 'exists:companies,id'],
            'company_location_ids' => ['nullable', 'array'],
            'arrival_location_ids' => ['nullable', 'array'],
            'arrival_sub_location_ids' => ['nullable', 'array'],

            // Product
            'product_id' => ['required', 'exists:products,id'],

            // Export Details
            'incoterm_id'         => ['required', 'exists:inco_terms,id'],
            'packing_type'        => ['required', 'string'],
            'mode_of_term_id'     => ['required', 'exists:mode_of_terms,id'],
            'mode_of_transport_id'=> ['required', 'exists:mode_of_transports,id'],
            'origin_country_id'   => ['required', 'exists:countries,id'],
            'port_of_discharge_id'=> ['required', 'exists:ports,id'],
            'port_of_loading_id'  => ['required', 'exists:ports,id'],

            // Payment
            'advance_payment' => ['nullable', 'numeric', 'min:0'],
            'payment_days'    => ['nullable', 'integer', 'min:0'],
            'currency_id'     => ['required', 'exists:currencies,id'],
            'currency_rate'   => ['nullable', 'numeric', 'min:0'],

            // Price
            'rate_per_kg'   => ['nullable', 'numeric', 'min:0'],
            'total_amount'  => ['nullable', 'numeric', 'min:0'],

            // Commission
            'commission_percentage'      => ['nullable', 'numeric', 'min:0'],
            'commission_amount_per_ton'  => ['nullable', 'numeric', 'min:0'],
            'commission'                 => ['nullable', 'numeric', 'min:0'],

            // Additional Info
            'additional_info' => ['nullable', 'string'],

            // Packing Items
            'packing_items'                     => ['required', 'array', 'min:1'],
            'packing_items.*.brand_id'          => ['nullable', 'exists:brands,id'],
            'packing_items.*.bag_type_id'       => ['required', 'exists:bag_types,id'],
            'packing_items.*.bag_packing_id'    => ['required', 'exists:bag_packings,id'],
            'packing_items.*.bag_condition_id'  => ['nullable', 'exists:bag_conditions,id'],
            'packing_items.*.bag_color_id'      => ['nullable', 'exists:colors,id'],
            'packing_items.*.bag_size'          => ['required', 'numeric', 'min:0'],
            'packing_items.*.metric_tons'       => ['required', 'numeric', 'gt:0'],
            'packing_items.*.maunds'            => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.no_of_bags'        => ['required', 'integer', 'min:0'],
            'packing_items.*.total_kgs'         => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.stuffing_in_container' => ['required', 'numeric', 'gt:0'],
            'packing_items.*.no_of_containers'  => ['required', 'integer', 'gt:0'],
            'packing_items.*.rate'              => ['required', 'numeric', 'gt:0'],
            'packing_items.*.rate_per_maund'    => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.amount'            => ['required', 'numeric', 'min:0'],
            'packing_items.*.amount_pkr'        => ['nullable', 'numeric', 'min:0'],

            // Specifications
            'specifications'                        => ['nullable', 'array'],
            'specifications.*.product_slab_type_id' => ['required', 'exists:product_slab_types,id'],
            'specifications.*.spec_name'            => ['required', 'string'],
            'specifications.*.spec_value'           => ['required'],
            'specifications.*.uom'                  => ['nullable', 'string'],
            'specifications.*.value_type'           => ['required', Rule::in(['min', 'max'])],
        ];
    }

    public function messages(): array
    {
        return [
            'buyer_id.required'   => 'Buyer is required',
            'company_id.required' => 'Company is required',
            'product_id.required' => 'Product is required',
            'packing_items.*.bag_type_id.required' => 'Bag type is required for all items',
            'packing_items.*.bag_packing_id.required' => 'Packing is required for all items',
            'packing_items.*.metric_tons.gt' => 'Quantity (MT) must be greater than 0',
            'packing_items.*.stuffing_in_container.gt' => 'Stuffing per container must be greater than 0',
            'packing_items.*.no_of_containers.gt' => 'Number of containers must be greater than 0',
            'packing_items.*.rate.gt' => 'Rate must be greater than 0',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'packing_items' => $this->packing_items ?? [],
            'export_soda_id' => $this->export_soda_id ?: null,
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->getMessages();
        $uniqueMessages = [];
        $finalErrors = [];

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                if (!in_array($message, $uniqueMessages)) {
                    $finalErrors[$field][] = $message;
                    $uniqueMessages[] = $message;
                } else {
                    // Still add the field to errors but with an empty message or indicator
                    // so the frontend can still mark the field as red
                    $finalErrors[$field][] = ''; 
                }
            }
        }

        throw new HttpResponseException(response()->json([
            'errors' => $finalErrors,
            'message' => 'The given data was invalid.'
        ], 422));
    }
}
