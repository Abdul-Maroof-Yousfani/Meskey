<?php

namespace App\Http\Requests\Arrival;

use Illuminate\Foundation\Http\FormRequest;

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
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            // 'supplier_name' => 'required|string|max:255',
            'miller_name' => 'required|string|max:255',
            //truck detail
            'arrival_truck_type_id' => 'required|max:255',
            'sample_money_type' => 'required|in:n/a,single,double',
            'sample_money' => 'required|numeric',
            'truck_no' => 'required|string|max:255',
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
     * Get custom messages for validation errors.
     */ public function messages(): array
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
            //        'accounts_of.string' => 'The accounts of field must be text.',

            'decision_id.required' => 'The decision of field is required.',
            'decision_id.exists' => 'The selected decision user does not exist.',


            'arrival_truck_type_id.required' => 'The truck type is required.',

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
