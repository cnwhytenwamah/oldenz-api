<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'status' => ['sometimes', Rule::in([
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])],
            'payment_status' => ['sometimes', Rule::in([
                'pending',
                'paid',
                'failed',
                'refunded',
                'partially_refunded'
            ])],
            'fulfillment_status' => ['sometimes', Rule::in([
                'unfulfilled',
                'partially_fulfilled',
                'fulfilled'
            ])],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'carrier' => ['nullable', 'string', 'max:255'],
            'admin_note' => ['nullable', 'string'],
            
            // Shipping address updates
            'shipping_first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'shipping_last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'shipping_phone' => ['sometimes', 'required', 'string', 'max:20'],
            'shipping_address_line_1' => ['sometimes', 'required', 'string', 'max:255'],
            'shipping_address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['sometimes', 'required', 'string', 'max:100'],
            'shipping_state' => ['sometimes', 'required', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['sometimes', 'required', 'string', 'max:100'],
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
            'status.in' => 'Invalid order status.',
            'payment_status.in' => 'Invalid payment status.',
            'fulfillment_status.in' => 'Invalid fulfillment status.',
            'tracking_number.max' => 'Tracking number is too long.',
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
            'admin_note' => 'admin note',
            'tracking_number' => 'tracking number',
        ];
    }
}
