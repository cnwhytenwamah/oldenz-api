<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            
            // Pricing
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'gte:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            
            // Inventory
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'stock_status' => ['nullable', Rule::in(['in_stock', 'out_of_stock', 'on_backorder'])],
            'track_inventory' => ['nullable', 'boolean'],
            
            // Product attributes
            'brand' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['men', 'women', 'unisex', 'kids'])],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string', 'max:50'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:50'],
            'materials' => ['nullable', 'array'],
            'materials.*' => ['string', 'max:100'],
            
            // Status
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new_arrival' => ['nullable', 'boolean'],
            'is_best_seller' => ['nullable', 'boolean'],
            'is_on_sale' => ['nullable', 'boolean'],
            
            // SEO
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['string', 'max:50'],
            
            // Relations
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            
            // Images
            'images' => ['nullable', 'array'],
            'images.*.path' => ['required_with:images', 'string'],
            'images.*.url' => ['required_with:images', 'string', 'url'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'sku.required' => 'Product SKU is required.',
            'sku.unique' => 'This SKU already exists.',
            'price.required' => 'Product price is required.',
            'price.min' => 'Price must be at least 0.',
            'compare_at_price.gte' => 'Compare at price must be greater than or equal to the regular price.',
            'stock_quantity.required' => 'Stock quantity is required.',
            'category_ids.*.exists' => 'One or more selected categories do not exist.',
        ];
    }
}
