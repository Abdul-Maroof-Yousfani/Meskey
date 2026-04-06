<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'incoterm_id'         => ['nullable', 'exists:inco_terms,id'],
            'packing_type'        => ['nullable', 'string'],
            'mode_of_term_id'     => ['nullable', 'exists:mode_of_terms,id'],
            'mode_of_transport_id'=> ['nullable', 'exists:mode_of_transports,id'],
            'origin_country_id'   => ['nullable', 'exists:countries,id'],
            'port_of_discharge_id'=> ['nullable', 'exists:ports,id'],
            'port_of_loading_id'  => ['nullable', 'exists:ports,id'],

            // Payment
            'advance_payment' => ['nullable', 'numeric', 'min:0'],
            'payment_days'    => ['nullable', 'integer', 'min:0'],
            'currency_id'     => ['nullable', 'exists:currencies,id'],
            'currency_rate'   => ['nullable', 'numeric', 'min:0'],

            // Quantity
            'stuffing_in_container'=> ['nullable', 'numeric', 'min:0'],
            'stuffing_maunds'      => ['nullable', 'numeric', 'min:0'],
            'no_of_containers'     => ['nullable', 'integer', 'min:0'],

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
            'packing_items'                     => ['nullable', 'array'],
            'packing_items.*.brand_id'          => ['nullable', 'exists:brands,id'],
            'packing_items.*.bag_type_id'       => ['nullable', 'exists:bag_types,id'],
            'packing_items.*.bag_packing_id'    => ['nullable', 'exists:bag_packings,id'],
            'packing_items.*.bag_condition_id'  => ['nullable', 'exists:bag_conditions,id'],
            'packing_items.*.bag_color_id'      => ['nullable', 'exists:colors,id'],
            'packing_items.*.bag_size'          => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.metric_tons'       => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.maunds'            => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.no_of_bags'        => ['nullable', 'integer', 'min:0'],
            'packing_items.*.total_kgs'         => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.rate'              => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.rate_per_maund'    => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.amount'            => ['nullable', 'numeric', 'min:0'],
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
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'packing_items' => $this->packing_items ?? [],
            'export_soda_id' => $this->export_soda_id ?: null,
        ]);
    }
}
