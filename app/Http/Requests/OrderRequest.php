<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Change to authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_name' => 'required|string',
            'customer_phone_no' => 'required|string',
            'customer_whats_app_no' => 'nullable|string',
            'flat_shop_number' => 'nullable|string',
            'building_no' => 'nullable|string',
            'road_no' => 'nullable|string',
            'block_no' => 'nullable|string',
            'city' => 'nullable|string',
            'is_delivery' => 'required|boolean',
            'urgent' => 'required|boolean',
            'order_items' => 'required|array|min:1',
            'order_items.*.service_id' => [
                'required',
                Rule::exists('services', 'id')->where(function ($query) {
                    $query->where('business_id', auth()->user()->business->id);
                }),
            ],
            'order_items.*.quantity' => 'required|integer|min:1',
            'status' => 'required|in:new,in-process,ready-for-delivery,delivered,completed,cancelled',

        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'order_items.*.service_id.exists' => 'The selected service is invalid or not available for your business.',
            'order_items.*.service_id.required' => 'The service field is required.',
        ];
    }
}
