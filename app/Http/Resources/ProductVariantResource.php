<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'name' => $this->name,
            
            'color' => $this->color,
            'size' => $this->size,
            'material' => $this->material,
            'attributes' => $this->attributes,
            
            'price' => [
                'amount' => $this->price ?? $this->product?->price,
                'formatted' => '₦' . number_format($this->price ?? $this->product?->price ?? 0, 2),
                'compare_at_price' => $this->compare_at_price,
                'compare_at_price_formatted' => $this->compare_at_price 
                    ? '₦' . number_format($this->compare_at_price, 2) 
                    : null,
                'discount_percentage' => $this->discount_percentage,
            ],
            
            'inventory' => [
                'stock_quantity' => $this->stock_quantity,
                'stock_status' => $this->stock_status?->value,
                'stock_status_label' => $this->stock_status?->label(),
                'is_in_stock' => $this->isInStock(),
                'is_low_stock' => $this->isLowStock(),
            ],

            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
