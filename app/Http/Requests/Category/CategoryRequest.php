<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'parent_id' => 'nullable|exists:categories,id',
            'category_type' => 'required|in:raw_finish,general_items',
            'name' => 'required|string|max:255',
            //this value set in middleware 
            'company_id' => 'required|exists:companies,id',
        ];
    }
}