<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class FreightPaymentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'arrival_slip_no' => 'nullable|string|max:255',
            'vendor_id' => 'required|exists:vendors,id',
            'contract_rate' => 'required|numeric|min:0',
            'exempt' => 'required|numeric|min:0',

            // Freight & Charges
            'freight_amount' => 'required|numeric|min:0',
            'freight_per_ton' => 'required|numeric|min:0',
            'loading_kanta' => 'required|numeric|min:0',
            'arrived_kanta' => 'required|numeric|min:0',

            // Additions
            'other_labour_positive' => 'required|numeric|min:0',
            'dehari_extra' => 'required|numeric|min:0',
            'market_comm' => 'required|numeric|min:0',

            // Deductions
            'over_weight_ded' => 'required|numeric|min:0',
            'godown_penalty' => 'required|numeric|min:0',
            'other_labour_negative' => 'required|numeric|min:0',
            'extra_ded' => 'required|numeric|min:0',
            'commission_ded' => 'required|numeric|min:0',

            // Payment Summary
            'gross_amount' => 'required|numeric|min:0',
            'total_deductions' => 'required|numeric|min:0',
            'net_amount' => 'required|numeric|min:0',
            'request_amount' => 'required|numeric|min:0|lte:net_amount',

            // Status (for admin approval)
            'status' => 'sometimes|in:pending,approved,rejected',
        ];
    }

    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {
        //     // Calculate and validate gross amount
        //     $calculatedGross =
        //         $this->input('freight_amount', 0) +
        //         $this->input('loading_kanta', 0) +
        //         $this->input('arrived_kanta', 0) +
        //         $this->input('other_labour_positive', 0) +
        //         $this->input('dehari_extra', 0) +
        //         $this->input('market_comm', 0);

        //     if ($this->input('gross_amount') != $calculatedGross) {
        //         $validator->errors()->add(
        //             'gross_amount',
        //             'Gross amount calculation mismatch. Should be: ' . $calculatedGross
        //         );
        //     }

        //     // Calculate and validate total deductions
        //     $calculatedDeductions =
        //         $this->input('over_weight_ded', 0) +
        //         $this->input('godown_penalty', 0) +
        //         $this->input('other_labour_negative', 0) +
        //         $this->input('extra_ded', 0) +
        //         $this->input('commission_ded', 0);

        //     if ($this->input('total_deductions') != $calculatedDeductions) {
        //         $validator->errors()->add(
        //             'total_deductions',
        //             'Total deductions calculation mismatch. Should be: ' . $calculatedDeductions
        //         );
        //     }

        //     // Calculate and validate net amount
        //     $calculatedNet = $calculatedGross - $calculatedDeductions;
        //     if ($this->input('net_amount') != $calculatedNet) {
        //         $validator->errors()->add(
        //             'net_amount',
        //             'Net amount calculation mismatch. Should be: ' . $calculatedNet
        //         );
        //     }

        //     // Validate request amount doesn't exceed net amount
        //     if ($this->input('request_amount', 0) > $calculatedNet) {
        //         $validator->errors()->add(
        //             'request_amount',
        //             'Request amount cannot exceed net amount of ' . $calculatedNet
        //         );
        //     }
        // });
    }

    public function messages()
    {
        return [
            'arrival_ticket_id.required' => 'Arrival ticket is required.',
            'arrival_ticket_id.exists' => 'Selected arrival ticket does not exist.',
            'vendor_id.required' => 'Vendor is required.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'contract_rate.required' => 'Contract rate is required.',
            'contract_rate.numeric' => 'Contract rate must be a number.',
            'contract_rate.min' => 'Contract rate must be at least 0.',
            'exempt.required' => 'Exempt amount is required.',
            'exempt.numeric' => 'Exempt amount must be a number.',
            'exempt.min' => 'Exempt amount must be at least 0.',

            // Freight & Charges messages
            'freight_amount.required' => 'Freight amount is required.',
            'freight_amount.numeric' => 'Freight amount must be a number.',
            'freight_amount.min' => 'Freight amount must be at least 0.',
            'freight_per_ton.required' => 'Freight per ton is required.',
            'freight_per_ton.numeric' => 'Freight per ton must be a number.',
            'freight_per_ton.min' => 'Freight per ton must be at least 0.',
            'loading_kanta.required' => 'Loading kanta charges are required.',
            'loading_kanta.numeric' => 'Loading kanta charges must be a number.',
            'loading_kanta.min' => 'Loading kanta charges must be at least 0.',
            'arrived_kanta.required' => 'Arrived kanta charges are required.',
            'arrived_kanta.numeric' => 'Arrived kanta charges must be a number.',
            'arrived_kanta.min' => 'Arrived kanta charges must be at least 0.',

            // Additions messages
            'other_labour_positive.required' => 'Other labour charges (+) are required.',
            'other_labour_positive.numeric' => 'Other labour charges (+) must be a number.',
            'other_labour_positive.min' => 'Other labour charges (+) must be at least 0.',
            'dehari_extra.required' => 'Dehari/Extra charges are required.',
            'dehari_extra.numeric' => 'Dehari/Extra charges must be a number.',
            'dehari_extra.min' => 'Dehari/Extra charges must be at least 0.',
            'market_comm.required' => 'Market commission is required.',
            'market_comm.numeric' => 'Market commission must be a number.',
            'market_comm.min' => 'Market commission must be at least 0.',

            // Deductions messages
            'over_weight_ded.required' => 'Over weight deduction is required.',
            'over_weight_ded.numeric' => 'Over weight deduction must be a number.',
            'over_weight_ded.min' => 'Over weight deduction must be at least 0.',
            'godown_penalty.required' => 'Godown penalty is required.',
            'godown_penalty.numeric' => 'Godown penalty must be a number.',
            'godown_penalty.min' => 'Godown penalty must be at least 0.',
            'other_labour_negative.required' => 'Other labour charges (-) are required.',
            'other_labour_negative.numeric' => 'Other labour charges (-) must be a number.',
            'other_labour_negative.min' => 'Other labour charges (-) must be at least 0.',
            'extra_ded.required' => 'Extra deduction is required.',
            'extra_ded.numeric' => 'Extra deduction must be a number.',
            'extra_ded.min' => 'Extra deduction must be at least 0.',
            'commission_ded.required' => 'Commission deduction is required.',
            'commission_ded.numeric' => 'Commission deduction must be a number.',
            'commission_ded.min' => 'Commission deduction must be at least 0.',

            // Payment Summary messages
            'gross_amount.required' => 'Gross amount is required.',
            'gross_amount.numeric' => 'Gross amount must be a number.',
            'gross_amount.min' => 'Gross amount must be at least 0.',
            'total_deductions.required' => 'Total deductions are required.',
            'total_deductions.numeric' => 'Total deductions must be a number.',
            'total_deductions.min' => 'Total deductions must be at least 0.',
            'net_amount.required' => 'Net amount is required.',
            'net_amount.numeric' => 'Net amount must be a number.',
            'net_amount.min' => 'Net amount must be at least 0.',
            'request_amount.required' => 'Request amount is required.',
            'request_amount.numeric' => 'Request amount must be a number.',
            'request_amount.min' => 'Request amount must be at least 0.',
            'request_amount.lte' => 'Request amount cannot exceed net amount.',

            // Status messages
            'status.in' => 'Status must be either pending, approved or rejected.',
        ];
    }
}
