<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class AddExpenseCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:expense_categories,name,NULL,id,deleted_at,NULL',
            'description' => 'nullable|string',
            'daily_estimate' => 'required|numeric|min:0|max:999999.999',
            'weekly_estimate' => 'required|numeric|min:0|max:999999.999',
            'monthly_estimate' => 'required|numeric|min:0|max:999999.999',
            'status' => 'required|in:0,1',
        ];
        

    }
}
