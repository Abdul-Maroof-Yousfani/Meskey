<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class AddExpenseRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:expenses,name,NULL,id,deleted_at,NULL',
            'category_id' => 'required|exists:expense_categories,id',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0|max:99999999.999', // Adjust max based on `decimal(10, 3)`
            'type_id' => 'required|in:1,2',
            'expense_date' => 'required|date'
        ];
        
        
    }
}
