<?php

namespace App\Http\Requests\Procurement;

use App\Models\Procurement\PurchaseFreight;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PurchaseFreightRequest extends FormRequest
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
            'arrival_purchase_order_id' => 'required|exists:arrival_purchase_orders,id',
            'loading_date' => 'required|date',
            'supplier_name' => 'required|string|max:255',
            'broker' => 'required|string|max:255',
            'truck_no' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $this->validateTruckNumberFormat($attribute, $value, $fail);
                },
                function ($attribute, $value, $fail) {
                    $this->validateUniqueTruckBiltyCombination($attribute, $value, $fail);
                }
            ],
            'bilty_no' => 'required|string|max:255',
            // 'station_id' => 'required|exists:stations,id',
            'station' => 'required|string|max:255',
            'no_of_bags' => 'required|integer|min:1',
            'bag_condition_id' => 'required|exists:bag_conditions,id',
            'commodity' => 'required|string|max:255',
            'loading_weight' => 'required|numeric|min:0',
            'kanta_charges' => 'nullable|numeric|min:0',
            'freight_on_bilty' => 'required|numeric|min:0',
            'advance_freight' => 'nullable|numeric|min:0',
            'bilty_slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'weighbridge_slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'supplier_bill' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    /**
     * Validate truck number format based on company location setting
     */
    protected function validateTruckNumberFormat($attribute, $value, $fail)
    {
        $truckNo = strtoupper($value);
        $truckFormat = Auth::user()->companyLocation->truck_no_format ?? 0;

        if ($truckFormat === 1 && !preg_match('/^[A-Z]+-\d+$/', $truckNo)) {
            $fail('Truck number must contain alphabets followed by a dash and then numbers (e.g., ABC-123)');
        }
    }

    /**
     * Validate unique truck_no and bilty_no combination
     */
    protected function validateUniqueTruckBiltyCombination($attribute, $value, $fail)
    {
        $existingTicket = PurchaseFreight::where('truck_no', strtoupper($value))
            ->where('bilty_no', $this->bilty_no)
            ->first();

        if ($existingTicket) {
            $viewLink = ' <a href="#" target="_blank" class="text-blue-600 hover:underline">View Details</a>';
            throw ValidationException::withMessages([
                'truck_no' => ['Truck with this Bilty No already exists.' . $viewLink],
            ]);
        }
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'arrival_purchase_order_id.required' => 'The purchase order is required.',
            'arrival_purchase_order_id.exists' => 'The selected purchase order does not exist.',

            'loading_date.required' => 'The loading date is required.',
            'loading_date.date' => 'The loading date must be a valid date.',

            'supplier_name.required' => 'The supplier name is required.',

            'broker.required' => 'The broker name is required.',

            'truck_no.required' => 'The truck number is required.',

            'bilty_no.required' => 'The bilty number is required.',

            'station_id.required' => 'The station is required.',
            'station_id.exists' => 'The selected station does not exist.',

            'no_of_bags.required' => 'The number of bags is required.',
            'no_of_bags.integer' => 'The number of bags must be a whole number.',
            'no_of_bags.min' => 'The number of bags must be at least 1.',

            'bag_condition_id.required' => 'The bag condition is required.',
            'bag_condition_id.exists' => 'The selected bag condition does not exist.',

            'commodity.required' => 'The commodity is required.',

            'loading_weight.required' => 'The loading weight is required.',
            'loading_weight.numeric' => 'The loading weight must be a number.',
            'loading_weight.min' => 'The loading weight cannot be negative.',

            'kanta_charges.numeric' => 'The kanta charges must be a number.',
            'kanta_charges.min' => 'The kanta charges cannot be negative.',

            'freight_on_bilty.required' => 'The freight on bilty is required.',
            'freight_on_bilty.numeric' => 'The freight on bilty must be a number.',
            'freight_on_bilty.min' => 'The freight on bilty cannot be negative.',

            'advance_freight.numeric' => 'The advance freight must be a number.',
            'advance_freight.min' => 'The advance freight cannot be negative.',

            'bilty_slip.required' => 'The bilty slip is required.',
            'bilty_slip.file' => 'The bilty slip must be a file.',
            'bilty_slip.mimes' => 'The bilty slip must be a file of type: jpg, jpeg, png, pdf.',
            'bilty_slip.max' => 'The bilty slip must not exceed 5MB.',

            'weighbridge_slip.required' => 'The weighbridge slip is required.',
            'weighbridge_slip.file' => 'The weighbridge slip must be a file.',
            'weighbridge_slip.mimes' => 'The weighbridge slip must be a file of type: jpg, jpeg, png, pdf.',
            'weighbridge_slip.max' => 'The weighbridge slip must not exceed 5MB.',

            'supplier_bill.file' => 'The supplier bill must be a file.',
            'supplier_bill.mimes' => 'The supplier bill must be a file of type: jpg, jpeg, png, pdf.',
            'supplier_bill.max' => 'The supplier bill must not exceed 5MB.',
        ];
    }
}
