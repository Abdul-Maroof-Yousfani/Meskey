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
        return [
            "average_weight_of_one_bag" => "required",
            "size" => "required",
            "bio" => "required",
            "smell" => "required",
            "printing" => "required",
            "bottom_stitching" => "required",
            "ready_to_pack" => "required",
            "remarks" => "required",
            "date" => "required",
            "accepted_quantity" => [
                "required",
                function($attribute, $value, $fail) {
                    $accepted_quantity = $this->accepted_quantity;
                    $rejected_quantity = $this->rejected_quantity;
                    $qty = $this->total_bags;

                    if(((int)$accepted_quantity + (int)$rejected_quantity) != $qty) {
                        $fail("Accepted quantity, and Rejected quantity should be equal to $qty");
                    }
                }
            ],
            "deduction_per_bag" => ["nullable"],
            "rejected_quantity" => [
                "required",
                function($attribute, $value, $fail) {
                    $accepted_quantity = $this->accepted_quantity;
                    $rejected_quantity = $this->rejected_quantity;
                    $qty = $this->total_bags;

                    if(((int)$accepted_quantity + (int)$rejected_quantity) != $qty) {
                        $fail("Accepted quantity, and Rejected quantity should be equal to $qty");
                    }
                }
            ], 
        ];
    }
}
