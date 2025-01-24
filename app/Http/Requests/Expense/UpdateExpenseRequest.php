<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
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
            'name' => "required|max:500|regex:/^[a-zA-Z0-9 ,\-\(\)]+$/|unique:expenses,name,{$id},id,deleted_at,NULL",
            'category_id' => 'sometimes|required|exists:expense_categories,id',
            'description' => 'nullable|string',
            'total' => 'sometimes|required|numeric|min:0|max:99999999.999',
            'type_id' => 'sometimes|required|in:1,2',
            'expense_date' => 'required|date'
        ];
        
        
    }
}
