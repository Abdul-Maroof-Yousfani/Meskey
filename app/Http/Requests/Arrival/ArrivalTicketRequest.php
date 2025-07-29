<?php

namespace App\Http\Requests\Arrival;

use App\Models\Arrival\ArrivalTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class ArrivalTicketRequest extends FormRequest
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
            'unique_no' => 'nullable|string|max:255|unique:arrival_tickets,unique_no,NULL,id,company_id,' . $this->company_id,
            'company_location_id' => 'required',
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'miller_name' => 'required|string|max:255',
            'arrival_truck_type_id' => 'required|max:255',
            'arrival_purchase_order_id' => 'nullable|exists:arrival_purchase_orders,id',
            'sample_money_type' => 'required|in:n/a,single,double',
            'sample_money' => 'required|numeric',
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
            'bags' => 'required|string|max:255',
            'loading_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
            'station' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'first_weight' => 'required|numeric',
            'second_weight' => 'required|numeric',
            'net_weight' => 'required|numeric|min:0',
            'broker_name' => 'required|string|max:255',
            'accounts_of' => 'required|string|max:255',
            'decision_id' => 'required|exists:users,id',
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
        $existingTicket = ArrivalTicket::where('truck_no', strtoupper($value))
            ->where('bilty_no', $this->bilty_no)
            ->first();

        if ($existingTicket) {
            $viewLink = ' <a href="' . route('ticket.show', $existingTicket->id) . '" target="_blank" class="text-blue-600 hover:underline">View Details</a>';
            throw ValidationException::withMessages([
                'truck_no' => ['Truck with this Bilty No already exists.' . $viewLink],
            ]);
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'truck_no' => strtoupper($this->truck_no),
            // 'company_location_id' => $this->company_location_id ?? auth()->user()->company_location_id
        ]);
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number must be unique for the selected company.',
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'product_id.required' => 'The product field is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'miller_id.required' => 'The miller is required.',
            'accounts_of.required' => 'The accounts of field is required.',
            'decision_id.required' => 'The decision of field is required.',
            'decision_id.exists' => 'The selected decision user does not exist.',
            'arrival_truck_type_id.required' => 'The truck type is required.',
            'arrival_purchase_order_id.exists' => 'The selected purchase order does not exist.',
            'sample_money_type.required' => 'The sample money type is required.',
            'sample_money_type.in' => 'The sample money type must be either single or double.',
            'sample_money.required' => 'The sample money is required.',
            'sample_money.numeric' => 'The sample money must be a number.',
            'truck_no.required' => 'The truck number is required.',
            'bilty_no.required' => 'The bilty number is required.',
            'bags.required' => 'The number of bags is required.',
            'loading_date.date' => 'The loading date must be a valid date.',
            'remarks.string' => 'Remarks must be a valid text.',
            'station_id.required' => 'The Station field is required.',
            'status.in' => 'The status must be either active or inactive.',
            'first_weight.required' => 'The first weight is required.',
            'first_weight.numeric' => 'The first weight must be a number.',
            'second_weight.required' => 'The second weight is required.',
            'second_weight.numeric' => 'The second weight must be a number.',
            'net_weight.required' => 'The net weight is required.',
            'net_weight.numeric' => 'The net weight must be a number.',
            'net_weight.min' => 'Please check your values. Net weight cannot be negative.',
        ];
    }
}
