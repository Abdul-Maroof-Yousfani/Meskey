<?php

namespace App\Http\Requests\Sales;

use App\Models\Sales\SalesOrder;
use Illuminate\Foundation\Http\FormRequest;

class DeliveryOrderRequest extends FormRequest
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
        $rules = [
            'customer_id' => 'required|numeric',
            'sale_order_id' => 'required|numeric',
            'dispatch_date' => 'required|date',
            'delivery_date' => 'required|date',
            'reference_no' => 'required',
            'payment_term_id' => 'nullable',
            'sauda_type' => 'required|in:pohanch,x-mill',
            'line_desc' => 'required',
            'remarks' => 'nullable|string',

            'item_id' => 'required',
            'item_id.*' => 'required',

            'qty' => 'required',
            'qty.*' => 'required',

            'rate' => 'required',
            'rate.*' => 'required',

            'brand_id' => 'required',
            'brand_id.*' => 'required',


            'bag_type' => 'required',
            'bag_type.*' => 'required',


            'bag_size' => 'required',
            'bag_size.*' => 'required',


            'no_of_bags' => "required",
            'no_of_bags.*' => 'required|numeric',

        ];

        $saleOrder = SalesOrder::find(request()->sale_order_id);
       
        if($saleOrder && $saleOrder->pay_type_id == 10) {
              $rules = array_merge($rules, [
                'advance_amount'   => 'required|numeric',
                'withhold_amount'  => 'nullable|numeric',
                'withhold_for_rv_id' => 'nullable|numeric',
            ]);
        }
      

        return $rules;
    }
}
