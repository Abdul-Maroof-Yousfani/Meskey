<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryRequest extends FormRequest
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
        $id = $this->id;

        return [
            'name' => "required|max:500|regex:/^[a-zA-Z0-9 ,\-\(\)]+$/|unique:expense_categories,name,{$id},id,deleted_at,NULL",
            'description' => 'nullable|string',
            'daily_estimate' => 'sometimes|numeric|min:0|max:999999.999',
            'weekly_estimate' => 'sometimes|numeric|min:0|max:999999.999',
            'monthly_estimate' => 'sometimes|numeric|min:0|max:999999.999',
            'status' => 'sometimes|required|in:0,1',
        ];
        
    }
}
