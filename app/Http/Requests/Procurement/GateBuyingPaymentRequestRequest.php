<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class GateBuyingPaymentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'ticket_id' => 'required|exists:arrival_tickets,id',
            'loading_type' => 'required|in:loading,without_loading',
            'supplier_name' => 'required|string|max:255',
            'contract_no' => 'required|string|max:255',
            'contract_rate' => 'required|numeric|min:0', 
            'truck_no' => 'nullable|string|max:255',
            'loading_date' => 'nullable|date',
            'bilty_no' => 'nullable|string|max:255',
            'station' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'remaining_amount' => 'required|numeric',
            'payment_request_amount' => 'required|numeric|min:0',
            // 'advance_freight' => 'required|numeric',
            // 'freight_pay_request_amount' => 'nullable|numeric|min:0',
            'sampling_results' => 'nullable|array',
            'sampling_results.*.slab_type_id' => 'required|exists:product_slab_types,id',
            'sampling_results.*.checklist_value' => 'required|numeric',
            'sampling_results.*.suggested_deduction' => 'required|numeric',
            'sampling_results.*.applied_deduction' => 'required|numeric',
            'sampling_results.*.deduction_amount' => 'required|numeric',
            'compulsory_results' => 'nullable|array',
            'compulsory_results.*.qc_param_id' => 'nullable|exists:arrival_compulsory_qc_params,id',
            'compulsory_results.*.applied_deduction' => 'required|numeric',
            'compulsory_results.*.deduction_amount' => 'required|numeric',
            'supplier_commission' => 'numeric',
        ];

        // Add conditional rules based on loading_type
        if ($this->input('loading_type') === 'loading') {
            $rules = array_merge($rules, [
                'no_of_bags' => 'required|integer|min:0',
                'loading_weight' => 'required|numeric|min:0',
                'avg_rate' => 'required|numeric|min:0',
                'bag_weight' => 'required|numeric|min:0',
                'bag_rate' => 'required|numeric|min:0',
                'bag_weight_total' => 'required|numeric|min:0',
                'bag_weight_amount' => 'required|numeric|min:0',
                'bag_rate' => 'required|numeric|min:0',
                'bag_rate_amount' => 'required|numeric|min:0',
                'loading_weighbridge_amount' => 'required|numeric|min:0',
            ]);
        } else {
            $rules = array_merge($rules, [
                'no_of_bags' => 'nullable|integer|min:0',
                'loading_weight' => 'nullable|numeric|min:0',
                'avg_rate' => 'nullable|numeric|min:0',
                'bag_weight' => 'nullable|numeric|min:0',
                'bag_rate' => 'nullable|numeric|min:0',
                'bag_weight_total' => 'nullable|numeric|min:0',
                'bag_weight_amount' => 'nullable|numeric|min:0',
                'bag_rate' => 'nullable|numeric|min:0',
                'bag_rate_amount' => 'nullable|numeric|min:0',
                'loading_weighbridge_amount' => 'nullable|numeric|min:0',
            ]);
        }

        if ($this->input('supplier_commission', 0) < 0) {
            $rules['broker_id'] = 'required|exists:brokers,id';
        }

        return $rules;
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $payment_request_amount = $this->input('payment_request_amount', 0);

            $payment_request_amount = floatval($payment_request_amount);

            if ($payment_request_amount <= 0) {
                $validator->errors()->add(
                    'payment_request_amount',
                    'Payment Request Amount must be greater than 0.'
                );
            }
        });
    }


    public function messages()
    {
        return [
            'ticket_id.required' => 'Ticket is required.',
            'ticket_id.exists' => 'Selected ticket does not exist.',
            'loading_type.required' => 'Loading type is required.',
            'loading_type.in' => 'Loading type must be either loading or without_loading.',
            'supplier_name.required' => 'Supplier name is required.',
            'supplier_name.string' => 'Supplier name must be a string.',
            'supplier_name.max' => 'Supplier name cannot exceed 255 characters.',
            'contract_no.required' => 'Contract number is required.',
            'contract_no.string' => 'Contract number must be a string.',
            'contract_no.max' => 'Contract number cannot exceed 255 characters.',
            'contract_rate.required' => 'Contract rate is required.',
            'supplier_commission.numeric' => 'Supplier commission must be a number.',
            'broker_id.required' => 'Broker is required when supplier commission is negative.',
            'broker_id.exists' => 'Selected broker does not exist.',
            'contract_rate.numeric' => 'Contract rate must be a number.',
            'contract_rate.min' => 'Contract rate must be at least 0.',
            'truck_no.string' => 'Truck number must be a string.',
            'truck_no.max' => 'Truck number cannot exceed 255 characters.',
            'loading_date.date' => 'Loading date must be a valid date.',
            'bilty_no.string' => 'Bilty number must be a string.',
            'bilty_no.max' => 'Bilty number cannot exceed 255 characters.',
            'station.string' => 'Station must be a string.',
            'station.max' => 'Station cannot exceed 255 characters.',
            'no_of_bags.required' => 'Number of bags is required.',
            'no_of_bags.integer' => 'Number of bags must be an integer.',
            'no_of_bags.min' => 'Number of bags must be at least 0.',
            'loading_weight.required' => 'Loading weight is required.',
            'loading_weight.numeric' => 'Loading weight must be a number.',
            'loading_weight.min' => 'Loading weight must be at least 0.',
            'avg_rate.required' => 'Average rate is required.',
            'avg_rate.numeric' => 'Average rate must be a number.',
            'avg_rate.min' => 'Average rate must be at least 0.',
            'bag_weight.required' => 'Bag weight is required.',
            'bag_weight.numeric' => 'Bag weight must be a number.',
            'bag_weight.min' => 'Bag weight must be at least 0.',
            'bag_weight_total.required' => 'Total bag weight is required.',
            'bag_weight_total.numeric' => 'Total bag weight must be a number.',
            'bag_weight_total.min' => 'Total bag weight must be at least 0.',
            'bag_weight_amount.required' => 'Bag weight amount is required.',
            'bag_weight_amount.numeric' => 'Bag weight amount must be a number.',
            'bag_weight_amount.min' => 'Bag weight amount must be at least 0.',
            'bag_rate.required' => 'Bag rate is required.',
            'bag_rate.numeric' => 'Bag rate must be a number.',
            'bag_rate.min' => 'Bag rate must be at least 0.',
            'bag_rate_amount.required' => 'Bag rate amount is required.',
            'bag_rate_amount.numeric' => 'Bag rate amount must be a number.',
            'bag_rate_amount.min' => 'Bag rate amount must be at least 0.',
            'loading_weighbridge_amount.required' => 'Loading weighbridge amount is required.',
            'loading_weighbridge_amount.numeric' => 'Loading weighbridge amount must be a number.',
            'loading_weighbridge_amount.min' => 'Loading weighbridge amount must be at least 0.',
            'total_amount.required' => 'Total amount is required.',
            'total_amount.numeric' => 'Total amount must be a number.',
            'total_amount.min' => 'Total amount must be at least 0.',
            'paid_amount.required' => 'Paid amount is required.',
            'paid_amount.numeric' => 'Paid amount must be a number.',
            'paid_amount.min' => 'Paid amount must be at least 0.',
            'remaining_amount.required' => 'Remaining amount is required.',
            'remaining_amount.numeric' => 'Remaining amount must be a number.',
            'payment_request_amount.required' => 'Payment request amount is required.',
            'payment_request_amount.numeric' => 'Payment request amount must be a number.',
            'payment_request_amount.min' => 'Payment request amount must be at least 0.',
            'advance_freight.required' => 'Advance freight is required.',
            'advance_freight.numeric' => 'Advance freight must be a number.',
            'advance_freight.min' => 'Advance freight must be at least 0.',
            'freight_pay_request_amount.numeric' => 'Freight pay request amount must be a number.',
            'freight_pay_request_amount.min' => 'Freight pay request amount must be at least 0.',
            'sampling_results.array' => 'Sampling results must be an array.',
            'sampling_results.*.slab_type_id.required' => 'Slab type is required in sampling results.',
            'sampling_results.*.slab_type_id.exists' => 'Selected slab type does not exist.',
            'sampling_results.*.checklist_value.required' => 'Checklist value is required in sampling results.',
            'sampling_results.*.checklist_value.numeric' => 'Checklist value must be a number.',
            'sampling_results.*.suggested_deduction.required' => 'Suggested deduction is required in sampling results.',
            'sampling_results.*.suggested_deduction.numeric' => 'Suggested deduction must be a number.',
            'sampling_results.*.applied_deduction.required' => 'Applied deduction is required in sampling results.',
            'sampling_results.*.applied_deduction.numeric' => 'Applied deduction must be a number.',
            'sampling_results.*.deduction_amount.required' => 'Deduction amount is required in sampling results.',
            'sampling_results.*.deduction_amount.numeric' => 'Deduction amount must be a number.',
            'compulsory_results.array' => 'Compulsory results must be an array.',
            'compulsory_results.*.qc_param_id.exists' => 'Selected QC param does not exist.',
            'compulsory_results.*.applied_deduction.required' => 'Applied deduction is required in compulsory results.',
            'compulsory_results.*.applied_deduction.numeric' => 'Applied deduction must be a number.',
            'compulsory_results.*.deduction_amount.required' => 'Deduction amount is required in compulsory results.',
            'compulsory_results.*.deduction_amount.numeric' => 'Deduction amount must be a number.',
        ];
    }
}
