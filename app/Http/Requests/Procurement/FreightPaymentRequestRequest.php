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
            'freight_rs' => 'required|numeric|min:0',
            'freight_per_ton' => 'required|numeric|min:0',
            'loading_kanta' => 'required|numeric|min:0',
            'arrived_kanta' => 'required|numeric|min:0',

            // Additions (+)
            'other_plus_labour' => 'required|numeric|min:0',
            'dehari_plus_extra' => 'required|numeric|min:0',
            'market_comm' => 'required|numeric|min:0',

            // Deductions (-)
            'over_weight_ded' => 'required|numeric|min:0',
            'godown_penalty' => 'required|numeric|min:0',
            'other_minus_labour' => 'required|numeric|min:0',
            'extra_minus_ded' => 'required|numeric|min:0',
            'commission_percent_ded' => 'required|numeric|min:0',
            'commission_amount' => 'required|numeric|min:0',

            // Payment Summary
            'gross_amount' => 'required|numeric|min:0',
            'total_deductions' => 'required|numeric|min:0',
            'net_amount' => 'required|numeric|min:0',
            'request_amount' => 'required|numeric|min:0|lte:net_amount',

            // Status
            'status' => 'sometimes|in:pending,approved,rejected',
        ];
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

            // Freight & Charges
            'freight_rs.required' => 'Freight (Rs) is required.',
            'freight_rs.numeric' => 'Freight (Rs) must be a number.',
            'freight_rs.min' => 'Freight (Rs) must be at least 0.',
            'freight_per_ton.required' => 'Freight per ton is required.',
            'freight_per_ton.numeric' => 'Freight per ton must be a number.',
            'freight_per_ton.min' => 'Freight per ton must be at least 0.',
            'loading_kanta.required' => 'Loading Kanta is required.',
            'loading_kanta.numeric' => 'Loading Kanta must be a number.',
            'loading_kanta.min' => 'Loading Kanta must be at least 0.',
            'arrived_kanta.required' => 'Arrived Kanta is required.',
            'arrived_kanta.numeric' => 'Arrived Kanta must be a number.',
            'arrived_kanta.min' => 'Arrived Kanta must be at least 0.',

            // Additions (+)
            'other_plus_labour.required' => 'Other (+)/Labour is required.',
            'other_plus_labour.numeric' => 'Other (+)/Labour must be a number.',
            'other_plus_labour.min' => 'Other (+)/Labour must be at least 0.',
            'dehari_plus_extra.required' => 'Dehari(+)/Extra is required.',
            'dehari_plus_extra.numeric' => 'Dehari(+)/Extra must be a number.',
            'dehari_plus_extra.min' => 'Dehari(+)/Extra must be at least 0.',
            'market_comm.required' => 'Market Commission is required.',
            'market_comm.numeric' => 'Market Commission must be a number.',
            'market_comm.min' => 'Market Commission must be at least 0.',

            // Deductions (-)
            'over_weight_ded.required' => 'Over Weight Deduction is required.',
            'over_weight_ded.numeric' => 'Over Weight Deduction must be a number.',
            'over_weight_ded.min' => 'Over Weight Deduction must be at least 0.',
            'godown_penalty.required' => 'Godown Penalty is required.',
            'godown_penalty.numeric' => 'Godown Penalty must be a number.',
            'godown_penalty.min' => 'Godown Penalty must be at least 0.',
            'other_minus_labour.required' => 'Other (-)/Labour is required.',
            'other_minus_labour.numeric' => 'Other (-)/Labour must be a number.',
            'extra_minus_ded.required' => 'Extra (-) Deduction is required.',
            'extra_minus_ded.numeric' => 'Extra (-) Deduction must be a number.',
            'commission_percent_ded.required' => 'Commission % Deduction is required.',
            'commission_percent_ded.numeric' => 'Commission % Deduction must be a number.',
            'commission_amount.required' => 'Commission Amount is required.',
            'commission_amount.numeric' => 'Commission Amount must be a number.',

            // Payment Summary
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

            // Status
            'status.in' => 'Status must be either pending, approved, or rejected.',
        ];
    }
}
