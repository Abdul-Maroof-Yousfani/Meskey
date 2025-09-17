<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ArrivalPurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $data = $this->all();
        $arrivalPurchaseOrderId = $data['id'] ?? null;

        $rules = [
            'company_id'              => 'required|integer|exists:companies,id',
            'contract_date'           => 'required|date',
            'company_location_id'     => 'required|integer|exists:company_locations,id',
            'sauda_type_id'           => 'required|integer|exists:sauda_types,id',
            'truck_size_range_id'     => 'nullable|integer|exists:truck_size_ranges,id',
            'account_of'              => 'nullable|integer|exists:users,id',
            'division_id'             => 'nullable|integer|exists:divisions,id',
            'supplier_id'             => 'required|integer|exists:suppliers,id',
            'supplier_commission'     => 'nullable|numeric',
            'broker_one_id'           => 'nullable|integer|exists:brokers,id',
            'broker_one_commission'   => 'nullable|numeric',
            'broker_two_id'           => 'nullable|integer|exists:brokers,id',
            'broker_two_commission'   => 'nullable|numeric',
            'broker_three_id'         => 'nullable|integer|exists:brokers,id',
            'broker_three_commission' => 'nullable|numeric',
            'product_id'              => 'required|integer|exists:products,id',
            'division_id'             => 'required|integer|exists:divisions,id',
            'line_type'               => 'nullable|in:bari,choti',
            'bag_weight'              => 'nullable|numeric',
            'bag_rate'                => 'nullable|numeric',
            'delivery_date'           => 'required|date',
            'credit_days'             => 'required|integer',
            'delivery_address'        => 'required|string',
            'rate_per_kg'             => 'required|numeric',
            'rate_per_mound'          => 'required|numeric',
            'rate_per_100kg'          => 'required|numeric',
            'calculation_type'        => 'required|in:trucks,quantity',
            'no_of_trucks'            => 'nullable|integer',
            'total_quantity'          => 'nullable|numeric',
            'min_quantity'            => 'required|numeric',
            'max_quantity'            => 'required|numeric',
            'min_bags'                => 'nullable|integer',
            'max_bags'                => 'nullable|integer',
            'weighbridge_from'        => 'nullable|string',
            'remarks'                 => 'nullable|string',
            'status'                  => 'in:draft,confirmed,completed,cancelled',
        ];

        if (isset($data['calculation_type']) && $data['calculation_type'] === 'quantity') {
            $rules['min_quantity_input'] = 'required|numeric|min:0';
            $rules['max_quantity_input'] = 'required|numeric|gt:min_quantity_input';
        }

        if ($arrivalPurchaseOrderId) {
            $rules['contract_no'] = 'required|string|unique:arrival_purchase_orders,contract_no,' . $arrivalPurchaseOrderId;
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $data = $this->all();

            if (
                (!empty($data['broker_one_commission']) && (empty($data['broker_one_id']) || $data['broker_one_id'] == null))
            ) {
                $validator->errors()->add('broker_one_id', 'Broker 1 is required if commission is entered.');
            }
            if (
                (!empty($data['broker_one_id']) && (empty($data['broker_one_commission']) && $data['broker_one_commission'] !== "0" && $data['broker_one_commission'] !== 0))
            ) {
                $validator->errors()->add('broker_one_commission', 'Broker 1 commission is required if broker is selected.');
            }

            if (
                (!empty($data['broker_two_commission']) && (empty($data['broker_two_id']) || $data['broker_two_id'] == null))
            ) {
                $validator->errors()->add('broker_two_id', 'Broker 2 is required if commission is entered.');
            }
            if (
                (!empty($data['broker_two_id']) && (empty($data['broker_two_commission']) && $data['broker_two_commission'] !== "0" && $data['broker_two_commission'] !== 0))
            ) {
                $validator->errors()->add('broker_two_commission', 'Broker 2 commission is required if broker is selected.');
            }

            if (
                (!empty($data['broker_three_commission']) && (empty($data['broker_three_id']) || $data['broker_three_id'] == null))
            ) {
                $validator->errors()->add('broker_three_id', 'Broker 3 is required if commission is entered.');
            }
            if (
                (!empty($data['broker_three_id']) && (empty($data['broker_three_commission']) && $data['broker_three_commission'] !== "0" && $data['broker_three_commission'] !== 0))
            ) {
                $validator->errors()->add('broker_three_commission', 'Broker 3 commission is required if broker is selected.');
            }
        });
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company field is required.',
            'company_id.integer' => 'The company field must be an integer.',
            'company_id.exists' => 'The selected company does not exist.',

            'contract_date.required' => 'The contract date field is required.',
            'contract_date.date' => 'The contract date must be a valid date.',

            'company_location_id.required' => 'The company location field is required.',
            'company_location_id.integer' => 'The company location field must be an integer.',
            'company_location_id.exists' => 'The selected company location does not exist.',

            'sauda_type_id.required' => 'The sauda type field is required.',
            'sauda_type_id.integer' => 'The sauda type field must be an integer.',
            'sauda_type_id.exists' => 'The selected sauda type does not exist.',

            'truck_size_range_id.integer' => 'The truck size range field must be an integer.',
            'truck_size_range_id.exists' => 'The selected truck size range does not exist.',

            'account_of.integer' => 'The account of field must be an integer.',
            'account_of.exists' => 'The selected account of user does not exist.',

            'division_id.required' => 'The division field is required.',
            'division_id.integer' => 'The division field must be an integer.',
            'division_id.exists' => 'The selected division does not exist.',

            'supplier_id.required' => 'The supplier field is required.',
            'supplier_id.integer' => 'The supplier field must be an integer.',
            'supplier_id.exists' => 'The selected supplier does not exist.',

            'supplier_commission.numeric' => 'The supplier commission field must be numeric.',

            'broker_one_id.integer' => 'The broker one field must be an integer.',
            'broker_one_id.exists' => 'The selected broker one does not exist.',
            'broker_one_commission.numeric' => 'The broker one commission field must be numeric.',

            'broker_two_id.integer' => 'The broker two field must be an integer.',
            'broker_two_id.exists' => 'The selected broker two does not exist.',
            'broker_two_commission.numeric' => 'The broker two commission field must be numeric.',

            'broker_three_id.integer' => 'The broker three field must be an integer.',
            'broker_three_id.exists' => 'The selected broker three does not exist.',
            'broker_three_commission.numeric' => 'The broker three commission field must be numeric.',

            'product_id.required' => 'The commodity field is required.',
            'product_id.integer' => 'The commodity field must be an integer.',
            'product_id.exists' => 'The selected commodity does not exist.',

            'line_type.in' => 'The line type must be either bari or choti.',

            'bag_weight.numeric' => 'The bag weight field must be numeric.',
            'bag_rate.numeric' => 'The bag rate field must be numeric.',

            'delivery_date.required' => 'The delivery date field is required.',
            'delivery_date.date' => 'The delivery date must be a valid date.',

            'credit_days.required' => 'The credit days field is required.',
            'credit_days.integer' => 'The credit days field must be an integer.',

            'delivery_address.required' => 'The delivery address field is required.',
            'delivery_address.string' => 'The delivery address field must be a string.',

            'rate_per_kg.required' => 'The rate per kg field is required.',
            'rate_per_kg.numeric' => 'The rate per kg field must be numeric.',

            'rate_per_mound.required' => 'The rate per mound field is required.',
            'rate_per_mound.numeric' => 'The rate per mound field must be numeric.',

            'rate_per_100kg.required' => 'The rate per 100kg field is required.',
            'rate_per_100kg.numeric' => 'The rate per 100kg field must be numeric.',

            'calculation_type.required' => 'The calculation type field is required.',
            'calculation_type.in' => 'The calculation type must be either trucks or quantity.',

            'no_of_trucks.integer' => 'The no of trucks field must be an integer.',

            'total_quantity.numeric' => 'The total quantity field must be numeric.',

            'min_quantity.required' => 'The minimum quantity field is required.',
            'min_quantity.numeric' => 'The minimum quantity field must be numeric.',

            'max_quantity.required' => 'The maximum quantity field is required.',
            'max_quantity.numeric' => 'The maximum quantity field must be numeric.',

            'min_bags.integer' => 'The minimum bags field must be an integer.',
            'max_bags.integer' => 'The maximum bags field must be an integer.',

            'weighbridge_from.string' => 'The weighbridge from field must be a string.',

            'remarks.string' => 'The remarks field must be a string.',

            'status.in' => 'The status must be one of: draft, confirmed, completed, or cancelled.',

            'min_quantity_input.required' => 'The minimum quantity input field is required when calculation type is quantity.',
            'min_quantity_input.numeric' => 'The minimum quantity input field must be numeric.',
            'min_quantity_input.min' => 'The minimum quantity input must be at least 0.',

            'max_quantity_input.required' => 'The maximum quantity input field is required when calculation type is quantity.',
            'max_quantity_input.numeric' => 'The maximum quantity input field must be numeric.',
            'max_quantity_input.gt' => 'The maximum quantity input must be greater than minimum quantity input.',

            'contract_no.required' => 'The contract number field is required.',
            'contract_no.string' => 'The contract number field must be a string.',
            'contract_no.unique' => 'This contract number already exists.',
        ];
    }
}
