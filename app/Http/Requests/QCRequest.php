<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QCRequest extends FormRequest
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
        $id = $this->purchase_receiving_data_id;
        $purchaseOrderReceivingData = \App\Models\Procurement\Store\PurchaseOrderReceivingData::find($id);
        $isBag = $purchaseOrderReceivingData && $purchaseOrderReceivingData->category_id == 38;

        return [
            "average_weight_of_one_bag" => $isBag ? "required" : "nullable",
            "sample_average_weight" => "nullable",
            "size" => $isBag ? "required" : "nullable",
            "bio" => $isBag ? "required" : "nullable",
            "smell" => $isBag ? "required" : "nullable",
            "printing" => $isBag ? "required" : "nullable",
            "bottom_stitching" => $isBag ? "required" : "nullable",
            "ready_to_pack" => $isBag ? "required" : "nullable",
            "remarks" => "nullable",
            "date" => "required",
            "accepted_quantity" => [
                "required",
                function($attribute, $value, $fail) use ($purchaseOrderReceivingData) {
                    $accepted_quantity = $this->accepted_quantity;
                    $rejected_quantity = $this->rejected_quantity;
                    $qty = $purchaseOrderReceivingData->qty ?? 0;

                    if(((int)$accepted_quantity + (int)$rejected_quantity) != $qty) {
                        $fail("Accepted quantity, and Rejected quantity should be equal to $qty");
                    }
                }
            ],
            "deduction_per_bag" => ["nullable"],
            "rejected_quantity" => [
                "required",
                function($attribute, $value, $fail) use ($purchaseOrderReceivingData) {
                    $accepted_quantity = $this->accepted_quantity;
                    $rejected_quantity = $this->rejected_quantity;
                    $qty = $purchaseOrderReceivingData->qty ?? 0;

                    if(((int)$accepted_quantity + (int)$rejected_quantity) != $qty) {
                        $fail("Accepted quantity, and Rejected quantity should be equal to $qty");
                    }
                }
            ],
            "net_weight" => $isBag ? "required|array|size:10" : "nullable|array",
            "net_weight.*" => $isBag ? "required|numeric|min:0.01" : "nullable|numeric",
            "bag_weight" => $isBag ? "required|array|size:10" : "nullable|array",
            "bag_weight.*" => $isBag ? "required|numeric|min:1" : "nullable|numeric",
        ];
    }
}
