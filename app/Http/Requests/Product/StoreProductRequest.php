<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'category_id' => 'required|exists:categories,id',
            'unit_of_measure_id' => 'required|exists:unit_of_measures,id',
            'unique_no' => 'nullable|unique:products,unique_no',
            'bag_weight_for_purchasing' => 'nullable|numeric',
            'product_type' => 'required|in:raw_material,finish_good,general_items',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')
                    ->where('company_id', $this->input('company_id') ?? $this->company_id)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'bardcode' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'nullable|numeric',
            // 'is_bag' => 'required',

            'product_category_flags' => 'nullable|in:head,by,b2',
            // 'is_child_product' => 'nullable|in:yes,no',
            // 'is_by_product' => 'nullable|in:yes,no',
            'status' => 'required|in:active,inactive'
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'category_id.required' => 'The category is required.',
            'unit_of_measure_id.required' => 'The unit of measure is required.',
            'unique_no.required' => 'The unique number is required.',
            'unique_no.unique' => 'This unique number already exists.',
            'name.required' => 'The product name is required.',
            'name.unique' => 'The product name has already been taken.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'The image size must be less than 2MB.',
            'price.numeric' => 'The price must be a number.',
        ];
    }
}
