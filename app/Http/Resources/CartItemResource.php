<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductVariantResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->price * $this->quantity;

        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            
            'product' => new ProductResource($this->whenLoaded('product')),
            'product_id' => $this->product_id,
            
            'variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'product_variant_id' => $this->product_variant_id,
            
            'quantity' => $this->quantity,
            'price' => $this->price,
            'price_formatted' => '₦' . number_format($this->price, 2),
            'subtotal' => round($subtotal, 2),
            'subtotal_formatted' => '₦' . number_format($subtotal, 2),
            
            'is_available' => $this->when(
                $this->relationLoaded('product'),
                fn() => $this->product?->isInStock()
            ),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
