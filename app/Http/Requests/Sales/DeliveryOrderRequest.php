<?php

namespace App\Http\Requests\Sales;

use App\Models\Sales\SalesOrder;
use App\Rules\DeliveryAfterDispatch;
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
            'dispatch_date' => ["required", "date", new DeliveryAfterDispatch(request()->delivery_date, request()->dispatch_date)],
            'delivery_date' => ["required", "date", new DeliveryAfterDispatch(request()->delivery_date, request()->dispatch_date)],
            'reference_no' => 'required',
            'payment_term_id' => 'nullable',
            'sauda_type' => 'required|in:pohanch,x-mill',
            'line_desc' => 'nullable',
            'remarks' => 'nullable|string',
            "location_id" => "required|numeric",
            "arrival_id" => "required",
            "storage_id" => "required",

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
                'advance_amount'   => 'nullable',
                'withhold_amount'  => 'nullable',
                'withhold_for_rv' => 'nullable',
                "receipt_vouchers" => "required",
                "receipt_vouchers.*" => "required"
            ]);
            if(request()->withhold_amount && request()->withhold_amount > 0) {
                $rules["withhold_for_rv"] = "required";
            }
        }
      

        return $rules;
    }

    // public function messages() {
    //     return [
    //         "line_desc.required" => 'Reference number is required'       
    //     ];
    // }
}
