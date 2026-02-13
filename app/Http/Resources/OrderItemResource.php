<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            
            'product' => [
                'id' => $this->product_id,
                'name' => $this->product_name,
                'sku' => $this->product_sku,
                'current_product' => new ProductResource($this->whenLoaded('product')),
            ],
            
            'variant' => [
                'id' => $this->product_variant_id,
                'name' => $this->variant_name,
                'attributes' => $this->variant_attributes,
            ],
            
            'pricing' => [
                'unit_price' => $this->unit_price,
                'unit_price_formatted' => '₦' . number_format($this->unit_price, 2),
                'quantity' => $this->quantity,
                'subtotal' => $this->subtotal,
                'subtotal_formatted' => '₦' . number_format($this->subtotal, 2),
                'discount_amount' => $this->discount_amount,
                'discount_formatted' => '₦' . number_format($this->discount_amount, 2),
                'total' => $this->total,
                'total_formatted' => '₦' . number_format($this->total, 2),
            ],
            
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
