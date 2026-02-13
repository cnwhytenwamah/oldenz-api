<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\PromoCodeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'customer_email' => $this->customer_email,
            
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status?->label(),
            'fulfillment_status' => $this->fulfillment_status,
            
            'amounts' => [
                'subtotal' => $this->subtotal,
                'subtotal_formatted' => '₦' . number_format($this->subtotal, 2),
                'discount_amount' => $this->discount_amount,
                'discount_formatted' => '₦' . number_format($this->discount_amount, 2),
                'shipping_fee' => $this->shipping_fee,
                'shipping_formatted' => '₦' . number_format($this->shipping_fee, 2),
                'tax_amount' => $this->tax_amount,
                'tax_formatted' => '₦' . number_format($this->tax_amount, 2),
                'total' => $this->total,
                'total_formatted' => '₦' . number_format($this->total, 2),
            ],
            
            'shipping_address' => [
                'first_name' => $this->shipping_first_name,
                'last_name' => $this->shipping_last_name,
                'phone' => $this->shipping_phone,
                'address_line_1' => $this->shipping_address_line_1,
                'address_line_2' => $this->shipping_address_line_2,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
            ],
            
            'billing_address' => [
                'first_name' => $this->billing_first_name,
                'last_name' => $this->billing_last_name,
                'phone' => $this->billing_phone,
                'address_line_1' => $this->billing_address_line_1,
                'address_line_2' => $this->billing_address_line_2,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postal_code' => $this->billing_postal_code,
                'country' => $this->billing_country,
            ],
            
            'customer_note' => $this->customer_note,
            'admin_note' => $this->when(
                $request->user() instanceof \App\Models\Admin,
                $this->admin_note
            ),
            
            'tracking' => [
                'tracking_number' => $this->tracking_number,
                'carrier' => $this->carrier,
            ],
            
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'promo_code' => new PromoCodeResource($this->whenLoaded('promoCode')),
            
            'dates' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'confirmed_at' => $this->confirmed_at?->toIso8601String(),
                'shipped_at' => $this->shipped_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            ],
        ];
    }
}
