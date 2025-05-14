<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'supplier_id'             => 'required|integer|exists:suppliers,id',
            'supplier_commission'     => 'nullable|numeric',
            'broker_one_id'           => 'nullable|integer|exists:brokers,id',
            'broker_one_commission'  => 'nullable|numeric',
            'broker_two_id'           => 'nullable|integer|exists:brokers,id',
            'broker_two_commission'  => 'nullable|numeric',
            'broker_three_id'        => 'nullable|integer|exists:brokers,id',
            'broker_three_commission' => 'nullable|numeric',
            'product_id'             => 'required|integer|exists:products,id',
            'line_type'              => 'nullable|in:bari,choti',
            'bag_weight'             => 'nullable|integer',
            'bag_rate'               => 'nullable|numeric',
            'delivery_date'          => 'required|date',
            'credit_days'           => 'nullable|integer',
            'delivery_address'       => 'required|string',
            'rate_per_kg'           => 'required|numeric',
            'rate_per_mound'         => 'required|numeric',
            'rate_per_100kg'        => 'required|numeric',
            'calculation_type'      => 'required|in:trucks,quantity',
            'no_of_trucks'         => 'nullable|integer',
            'total_quantity'      => 'nullable|numeric',
            'min_quantity'         => 'required|numeric',
            'max_quantity'        => 'required|numeric',
            'min_bags'            => 'required|integer',
            'max_bags'            => 'required|integer',
            'weighbridge_from'    => 'nullable|numeric',
            'remarks'             => 'nullable|string',
            'status'              => 'in:draft,confirmed,completed,cancelled',
        ];

        if ($arrivalPurchaseOrderId) {
            $rules['contract_no'] = 'required|string|unique:arrival_purchase_orders,contract_no,' . $arrivalPurchaseOrderId;
        } else {
            $rules['contract_no'] = 'required|string|unique:arrival_purchase_orders,contract_no';
        }

        return $rules;
    }
}
