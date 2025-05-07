<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

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
            'bag_weight_for_purchasing' => 'required|numeric',
            'product_type' => 'required|in:raw_material,finish_good',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bardcode' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'nullable|numeric',
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
            'image.image' => 'The file must be an image.',
            'image.max' => 'The image size must be less than 2MB.',
            'price.numeric' => 'The price must be a number.',
        ];
    }
}
