<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            // Shipping Address
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:20'],
            'shipping_address.address_line_1' => ['required', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.state' => ['required', 'string', 'max:100'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'max:100'],

            // Billing Address (optional if same as shipping)
            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'billing_address.first_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.last_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.phone' => ['required_if:billing_same_as_shipping,false', 'string', 'max:20'],
            'billing_address.address_line_1' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_if:billing_same_as_shipping,false', 'string', 'max:100'],
            'billing_address.state' => ['required_if:billing_same_as_shipping,false', 'string', 'max:100'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.country' => ['required_if:billing_same_as_shipping,false', 'string', 'max:100'],

            // Payment & Additional Info
            'payment_method' => ['required', Rule::in(['paystack', 'flutterwave', 'bank_transfer'])],
            'promo_code' => ['nullable', 'string', 'exists:promo_codes,code'],
            'customer_note' => ['nullable', 'string', 'max:500'],
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
            'shipping_address.first_name.required' => 'Shipping first name is required.',
            'shipping_address.last_name.required' => 'Shipping last name is required.',
            'shipping_address.phone.required' => 'Shipping phone number is required.',
            'shipping_address.address_line_1.required' => 'Shipping address is required.',
            'shipping_address.city.required' => 'Shipping city is required.',
            'shipping_address.state.required' => 'Shipping state is required.',
            'shipping_address.country.required' => 'Shipping country is required.',
            
            'billing_address.first_name.required_if' => 'Billing first name is required.',
            'billing_address.last_name.required_if' => 'Billing last name is required.',
            'billing_address.phone.required_if' => 'Billing phone number is required.',
            'billing_address.address_line_1.required_if' => 'Billing address is required.',
            'billing_address.city.required_if' => 'Billing city is required.',
            'billing_address.state.required_if' => 'Billing state is required.',
            'billing_address.country.required_if' => 'Billing country is required.',

            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
            'promo_code.exists' => 'Invalid promo code.',
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
            'shipping_address.first_name' => 'first name',
            'shipping_address.last_name' => 'last name',
            'shipping_address.phone' => 'phone number',
            'shipping_address.address_line_1' => 'address',
            'shipping_address.city' => 'city',
            'shipping_address.state' => 'state',
            'shipping_address.country' => 'country',
        ];
    }
}
