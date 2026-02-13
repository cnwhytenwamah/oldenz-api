<?php

namespace App\Http\Requests\Frontend;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
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
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'product_variant_id.exists' => 'The selected product variant does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'You can add a maximum of 100 items at once.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'product_variant_id' => 'product variant',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->product_id) {
                $product = Product::find($this->product_id);
                
                if ($product && !$product->is_active) {
                    $validator->errors()->add('product_id', 'This product is not available.');
                }
            }

            if ($this->product_variant_id && $this->product_id) {
                $variant = ProductVariant::find($this->product_variant_id);
                
                if ($variant && $variant->product_id != $this->product_id) {
                    $validator->errors()->add('product_variant_id', 'This variant does not belong to the selected product.');
                }
            }
        });
    }
}
