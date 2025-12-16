<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportOrderRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            /* ================= BASIC INFO ================= */

            'voucher_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('export_orders')->ignore($this->route('export_order')),
            ],
            'contract_no' => ['required', 'string', 'max:100'],
            'voucher_date' => ['required', 'date'],
            'voucher_heading' => ['required', 'string', 'max:255'],

            'shipment_delivery_date_from' => ['required', 'date'],
            'shipment_delivery_date_to' => [
                'required',
                'date',
                'after_or_equal:shipment_delivery_date_from',
            ],

            'other_specifications' => ['required', 'string'],

            /* ================= FOREIGN KEYS ================= */

            'company_id' => ['required', 'exists:companies,id'],
            'buyer_id' => ['required', 'exists:users,id'],
            'product_id' => ['required', 'exists:products,id'],
            'bank_id' => ['required', 'exists:banks,id'],
            'correspondent_bank_id' => ['required', 'exists:banks,id'],
            'incoterm_id' => ['required', 'exists:inco_terms,id'],
            'mode_of_term_id' => ['required', 'exists:mode_of_terms,id'],
            'mode_of_transport_id' => ['required', 'exists:mode_of_transports,id'],
            'origin_country_id' => ['required', 'exists:countries,id'],
            'port_of_discharge_id' => ['required', 'exists:ports,id'],
            'port_of_loading_id' => ['required', 'exists:ports,id'],
            'hs_code_id' => ['required', 'exists:hs_codes,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'broker_id' => ['required', 'exists:brokers,id'],

            /* ================= PAYMENT ================= */

            'advance_payment' => ['required', 'numeric', 'min:0'],
            'currency_rate' => ['required', 'numeric', 'min:0'],
            'payment_days' => ['required', 'integer', 'min:0'],

            'partial_payment' => ['required', 'string'],
            'transhipment' => ['required', 'string'],
            'part_shipment' => ['required', 'string'],
            'insurance_covered_by' => ['required', 'string'],
            'packing_type' => ['required', 'string'],

            /* ================= TEXT AREAS ================= */

            'marking_labeling' => ['required', 'string'],
            'shipping_instructions' => ['nullable', 'string'],
            'documents_to_be_provided' => ['required', 'string'],
            'other_condition' => ['nullable', 'string'],
            'force_majure' => ['nullable', 'string'],
            'application_law' => ['required', 'string'],

            /* ================= LOCATIONS ================= */

            'company_location_ids' => ['nullable', 'array'],
            'company_location_ids.*' => ['exists:company_locations,id'],

            'arrival_location_ids' => ['nullable', 'array'],
            'arrival_location_ids.*' => ['exists:arrival_locations,id'],

            'arrival_sub_location_ids' => ['nullable', 'array'],
            'arrival_sub_location_ids.*' => ['exists:arrival_sub_locations,id'],

            /* ================= PACKING ITEMS ================= */

            'packing_items' => ['nullable', 'array'],
            'packing_items.*.brand_id' => ['required', 'exists:brands,id'],
            'packing_items.*.bag_type_id' => ['required', 'exists:bag_types,id'],
            'packing_items.*.bag_packing_id' => ['nullable', 'exists:bag_packings,id'],
            'packing_items.*.bag_condition_id' => ['required', 'exists:bag_conditions,id'],
            'packing_items.*.bag_color_id' => ['required', 'exists:colors,id'],
            'packing_items.*.bag_size' => ['required', 'numeric', 'min:0.1'],
            'packing_items.*.metric_tons' => ['required', 'numeric', 'min:0.001'],
            'packing_items.*.stuffing_in_container' => ['nullable', 'numeric', 'min:0'],
            'packing_items.*.no_of_containers' => ['nullable', 'integer', 'min:0'],
            'packing_items.*.rate' => ['required', 'numeric', 'min:0'],
        ];
    }

    /* ================= ERROR MESSAGES ================= */

    public function messages(): array
    {
        return [

            // Basic Info
            'voucher_no.required' => 'Voucher number is required',
            'voucher_no.unique' => 'This voucher number already exists',
            'contract_no.required' => 'Contract number is required',
            'voucher_date.required' => 'Voucher date is required',
            'voucher_heading.required' => 'Voucher heading is required',

            'shipment_delivery_date_from.required' => 'Shipment start date is required',
            'shipment_delivery_date_to.required' => 'Shipment end date is required',
            'shipment_delivery_date_to.after_or_equal' => 'Shipment end date must be after or equal to shipment start date',

            'other_specifications.required' => 'Other specifications are required',

            // Foreign Keys
            'company_id.required' => 'Company is required',
            'company_id.exists' => 'Selected company does not exist',

            'buyer_id.required' => 'Buyer is required',
            'buyer_id.exists' => 'Selected buyer does not exist',

            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Selected product does not exist',

            'bank_id.required' => 'Bank is required',
            'bank_id.exists' => 'Selected bank does not exist',

            'correspondent_bank_id.required' => 'Correspondent bank is required',
            'correspondent_bank_id.exists' => 'Selected correspondent bank does not exist',

            'incoterm_id.required' => 'Incoterm is required',
            'incoterm_id.exists' => 'Selected incoterm does not exist',

            'mode_of_term_id.required' => 'Mode of term is required',
            'mode_of_transport_id.required' => 'Mode of transport is required',

            'origin_country_id.required' => 'Country of origin is required',
            'port_of_discharge_id.required' => 'Port of discharge is required',
            'port_of_loading_id.required' => 'Port of loading is required',

            'hs_code_id.required' => 'HS code is required',
            'currency_id.required' => 'Currency is required',
            'broker_id.required' => 'Broker is required',

            // Payment
            'advance_payment.required' => 'Advance payment is required',
            'currency_rate.required' => 'Currency rate is required',
            'payment_days.required' => 'Payment days are required',

            'partial_payment.required' => 'Partial payment is required',
            'transhipment.required' => 'Transhipment information is required',
            'part_shipment.required' => 'Part shipment information is required',
            'insurance_covered_by.required' => 'Insurance covered by is required',
            'packing_type.required' => 'Packing type is required',

            // Text Areas
            'marking_labeling.required' => 'Marking & labeling is required',
            'documents_to_be_provided.required' => 'Documents details are required',
            'application_law.required' => 'Application law is required',

            // locations
            'company_location_ids.array' => 'Company locations must be a valid list',
            'company_location_ids.*.exists' => 'One or more selected company locations are invalid',

            'arrival_location_ids.array' => 'Arrival locations must be a valid list',
            'arrival_location_ids.*.exists' => 'One or more selected arrival locations are invalid',

            'arrival_sub_location_ids.array' => 'Arrival sub locations must be a valid list',
            'arrival_sub_location_ids.*.exists' => 'One or more selected arrival sub locations are invalid',

            // Packing Items
            'packing_items.*.brand_id.required' => 'Brand is required',
            'packing_items.*.bag_type_id.required' => 'Bag type is required',
            'packing_items.*.bag_condition_id.required' => 'Bag condition is required',
            'packing_items.*.bag_color_id.required' => 'Bag color is required',
            'packing_items.*.bag_size.required' => 'Bag size is required',
            'packing_items.*.bag_size.min' => 'Bag size must be at least 0.1',
            'packing_items.*.metric_tons.required' => 'Quantity (MTs) is required',
            'packing_items.*.metric_tons.min' => 'Quantity (MTs) must be at least 0.001',
            'packing_items.*.rate.required' => 'Rate per ton is required',
            'packing_items.*.rate.min' => 'Rate per ton must be at least 0',
        ];
    }

    /* ================= ATTRIBUTE NAMES ================= */

    public function attributes(): array
    {
        return [
            'voucher_no' => 'voucher number',
            'contract_no' => 'contract number',
            'voucher_date' => 'voucher date',
            'shipment_delivery_date_from' => 'shipment start date',
            'shipment_delivery_date_to' => 'shipment end date',
            'other_specifications' => 'other specifications',
            'company_id' => 'company',
            'buyer_id' => 'buyer',
            'product_id' => 'product',
            'incoterm_id' => 'incoterm',
            'company_location_ids' => 'company locations',
            'arrival_location_ids' => 'arrival locations',
            'arrival_sub_location_ids' => 'arrival sub locations',
            'packing_items' => 'packing items',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'company_location_ids' => $this->company_location_ids ?? [],
            'arrival_location_ids' => $this->arrival_location_ids ?? [],
            'arrival_sub_location_ids' => $this->arrival_sub_location_ids ?? [],
            'packing_items' => $this->packing_items ?? [],
        ]);
    }
}
