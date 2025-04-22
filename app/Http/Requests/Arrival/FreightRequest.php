<?php

namespace App\Http\Requests\Arrival;

use Illuminate\Foundation\Http\FormRequest;

class FreightRequest extends FormRequest
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
    public function rules()
    {
        return [
            'ticket_id' => 'required|exists:arrival_tickets,id',
            'ticket_number' => 'required|string|max:255',
            'supplier' => 'required|string|max:255',
            'commodity' => 'required|string|max:255',
            'truck_number' => 'required|string|max:255',
            'billy_number' => 'required|string|max:255',
            // 'loaded_weight' => 'required|integer',
            // 'arrived_weight' => 'required|integer',
            'freight_per_ton' => 'required|numeric',
            'bilty_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'loading_weight_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'other_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'other_document_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    // public function messages(): array
    // {
    //     return [
    //         'unique_no.required' => 'The unique number is required.',
    //         'unique_no.unique' => 'The unique number must be unique for the selected company.',

    //         'company_id.required' => 'The company field is required.',
    //         'company_id.exists' => 'The selected company does not exist.',

    //         'product_id.required' => 'The product field is required.',
    //         'product_id.exists' => 'The selected product does not exist.',

    //         'supplier_name.required' => 'The supplier name is required.',


    //         'accounts_of.required' => 'The accounts of field is required.',
    //         //        'accounts_of.string' => 'The accounts of field must be text.',

    //         'decision_id.required' => 'The decision of field is required.',
    //         'decision_id.exists' => 'The selected decision user does not exist.',


    //         'arrival_truck_type_id.required' => 'The truck type is required.',

    //         'sample_money_type.required' => 'The sample money type is required.',
    //         'sample_money_type.in' => 'The sample money type must be either single or double.',

    //         'sample_money.required' => 'The sample money is required.',
    //         'sample_money.numeric' => 'The sample money must be a number.',

    //         'truck_no.required' => 'The truck number is required.',
    //         'bilty_no.required' => 'The bilty number is required.',

    //         'bags.required' => 'The number of bags is required.',

    //         'loading_date.date' => 'The loading date must be a valid date.',

    //         'remarks.string' => 'Remarks must be a valid text.',

    //         'station_name.required' => 'The Station field is required.',

    //         'status.in' => 'The status must be either active or inactive.',

    //         'first_weight.required' => 'The first weight is required.',
    //         'first_weight.numeric' => 'The first weight must be a number.',

    //         'second_weight.required' => 'The second weight is required.',
    //         'second_weight.numeric' => 'The second weight must be a number.',

    //         'net_weight.required' => 'The net weight is required.',
    //         'net_weight.numeric' => 'The net weight must be a number.',

    //     ];
    // }
}
