<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GateBuyingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $gateBuyingId = $this->route('gate_buying') ? $this->route('gate_buying') : null;

        $rules = [
            'purchase_type' => 'required|in:gate_buying',
            'company_location_id' => 'required|integer|exists:company_locations,id',
            'contract_date' => 'required|date',
            'ref_no' => 'required|string|max:100',
            'supplier_name' => 'required|string|max:255',
            'purchaser_name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_no' => 'nullable|string|max:20',
            'broker_one_id' => 'nullable|integer|exists:brokers,id',
            'broker_one_commission' => 'nullable|numeric|min:0',
            'product_id' => 'required|integer|exists:products,id',
            'rate_per_kg' => 'required|numeric|min:0',
            'rate_per_mound' => 'required|numeric|min:0',
            'rate_per_100kg' => 'required|numeric|min:0',
            'truck_no' => 'nullable|string|max:50',
            'payment_term' => 'required|in:Cash Payment,Cheque,Online',
            'remarks' => 'nullable|string',
            'created_by' => 'required|integer|exists:users,id',
        ];

        if ($gateBuyingId) {
            $rules['contract_no'] = 'required|string|max:100|unique:arrival_purchase_orders,contract_no,' . $gateBuyingId;
        } else {
            $rules['contract_no'] = 'required|string|max:100|unique:arrival_purchase_orders,contract_no';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'company_location_id.required' => 'Please select a location',
            'contract_date.required' => 'Contract date is required',
            'ref_no.required' => 'Reference number is required',
            'supplier_name.required' => 'Supplier name is required',
            'purchaser_name.required' => 'Purchaser name is required',
            'product_id.required' => 'Please select a Commodity',
            'rate_per_kg.required' => 'Rate per kg is required',
            'rate_per_mound.required' => 'Rate per mound is required',
            'rate_per_100kg.required' => 'Rate per 100kg is required',
            'payment_term.required' => 'Please select a payment term',
            'contract_no.unique' => 'This contract number already exists',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate rate consistency
            $ratePerKg = $this->input('rate_per_kg');
            $ratePerMound = $this->input('rate_per_mound');
            $ratePer100kg = $this->input('rate_per_100kg');

            $calculatedMound = $ratePerKg * 40;
            $calculated100kg = $ratePerKg * 100;

            if (abs($calculatedMound - $ratePerMound) > 0.01) {
                $validator->errors()->add('rate_per_mound', 'Rate per mound should be exactly 40 times rate per kg');
            }

            if (abs($calculated100kg - $ratePer100kg) > 0.01) {
                $validator->errors()->add('rate_per_100kg', 'Rate per 100kg should be exactly 100 times rate per kg');
            }
        });
    }
}
