<?php

namespace App\Http\Requests\Master;

use App\Models\Master\Broker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrokerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('company_id') || empty($this->company_id)) {
            $companyId = auth()->user()?->current_company_id ?? auth()->user()?->company_id ?? 1;
            $this->merge(['company_id' => $companyId]);
        }
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: (auth()->user()?->current_company_id ?? auth()->user()?->company_id ?? 1);
        $brokerParam = $this->route('broker');
        $brokerId = $brokerParam instanceof Broker
            ? $brokerParam->id
            : ($brokerParam ?? $this->broker);

        $checkDuplicateBroker = function ($attribute, $value, $fail) use ($companyId, $brokerId) {
            $companyName = trim($this->input('company_name') ?? '');
            $ownerName = trim($this->input('owner_name') ?? '');

            if ($companyName === '' || $ownerName === '') {
                return;
            }

            // Strict check: Same company_name with same owner_name cannot be duplicated
            $exists = Broker::where('company_id', $companyId)
                ->where(function ($q) use ($companyName, $ownerName) {
                    $q->where(function ($sq) use ($companyName, $ownerName) {
                        $sq->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
                           ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)]);
                    })
                    ->orWhere(function ($sq) use ($companyName, $ownerName) {
                        $sq->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                           ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)]);
                    });
                })
                ->when($brokerId, function ($q) use ($brokerId) {
                    $q->where('id', '!=', $brokerId);
                })
                ->exists();

            if ($exists) {
                $fail("A broker with Company Name '{$companyName}' and Owner Name '{$ownerName}' already exists.");
            }
        };

        return [
            'company_id' => 'required|exists:companies,id',
            'unique_no' => 'nullable|string|max:255|unique:brokers,unique_no' . ($brokerId ? ",$brokerId" : ''),
            'company_name' => [
                'required',
                'string',
                'max:255',
                $checkDuplicateBroker,
            ],
            // 'account_type' => 'required|in:credit,debit',
            'owner_name' => [
                'required',
                'string',
                'max:255',
                $checkDuplicateBroker,
            ],



            'owner_mobile_no' => 'required|string|max:11|regex:/^[0-9]{11}$/',
            'owner_cnic_no' => 'required|string|regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/',
            'next_to_kin' => 'nullable|string|max:255',
            'next_to_kin_mobile_no' => 'nullable|string|max:11|regex:/^[0-9]{11}$/',



            'owner_bank_detail' => 'nullable|string|max:255',
            'company_bank_detail' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'ntn' => 'nullable|string|max:15',
            'stn' => 'nullable|string|max:15',
            'attachment' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'is_for_sales' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_mobile_no.regex' => 'Mobile number must be exactly 11 digits.',
            'next_to_kin_mobile_no.regex' => 'Mobile number must be exactly 11 digits.',
            'owner_cnic_no.regex' => 'CNIC must be in format: 12345-1234567-1',
            'company_id.required' => 'The company is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'The unique number must be unique.',
            'name.unique' => 'The name has already been taken for the selected company.',
            'name.required' => 'The name is required.',
            'company_name.required' => 'The company name is required.',
            'owner_name.required' => 'The owner name is required.',
            'owner_mobile_no.required' => 'The owner mobile number is required.',
            'owner_cnic_no.string' => 'The owner CNIC must be a valid string.',
            'next_to_kin.string' => 'The next to kin must be a valid string.',
            'next_to_kin_mobile_no.string' => 'The next to kin mobile number must be a valid string.',
            'owner_bank_detail.string' => 'The owner bank detail must be a valid string.',
            'company_bank_detail.string' => 'The company bank detail must be a valid string.',
            'prefix.string' => 'The prefix must be a valid string.',
            'email.email' => 'Please provide a valid email address.',
            'phone.string' => 'The phone number must be a valid string.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
