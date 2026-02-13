<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StorePromoCodeRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:promo_codes,code', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date', 'after_or_equal:today'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'applicable_categories' => ['nullable', 'array'],
            'applicable_categories.*' => ['integer', 'exists:categories,id'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_products.*' => ['integer', 'exists:products,id'],
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
            'code.required' => 'Promo code is required.',
            'code.unique' => 'This promo code already exists.',
            'code.alpha_dash' => 'Promo code can only contain letters, numbers, dashes and underscores.',
            'discount_type.required' => 'Discount type is required.',
            'discount_type.in' => 'Discount type must be percentage or fixed.',
            'discount_value.required' => 'Discount value is required.',
            'discount_value.min' => 'Discount value must be greater than 0.',
            'expires_at.after' => 'Expiry date must be after start date.',
            'applicable_categories.*.exists' => 'One or more selected categories do not exist.',
            'applicable_products.*.exists' => 'One or more selected products do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }
    }
}
