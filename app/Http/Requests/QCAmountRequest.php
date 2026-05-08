<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QCAmountRequest extends FormRequest
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
            "accepted_quantity" => [
                "required",
                function($attribute, $value, $fail) {
                    $accepted_quantity = $this->accepted_quantity;
                    $rejected_quantity = $this->rejected_quantity;
                    $rejection_return = $this->rejection_return ?? 0;
                    $qty = $this->total_bags;

                    if((round((float)$accepted_quantity + (float)$rejected_quantity + (float)$rejection_return, 2)) != round((float)$qty, 2)) {
                        $fail("Accepted quantity, Rejected quantity, and Rejection Return should be equal to $qty");
                    }
                }
            ],
            "deduction_per_bag" => ["nullable"],
            "rejection_return" => ["required", "numeric", "min:0"],
            "rejected_quantity" => [
                "required",
                function($attribute, $value, $fail) {
                    $accepted_quantity = $this->accepted_quantity;
                    $rejected_quantity = $this->rejected_quantity;
                    $rejection_return = $this->rejection_return ?? 0;
                    $qty = $this->total_bags;

                    if((round((float)$accepted_quantity + (float)$rejected_quantity + (float)$rejection_return, 2)) != round((float)$qty, 2)) {
                        $fail("Accepted quantity, Rejected quantity, and Rejection Return should be equal to $qty");
                    }
                }
            ],  
        ];
    }
}
