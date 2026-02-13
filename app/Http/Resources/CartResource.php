<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CartItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->items->sum(fn($item) => $item->price * $item->quantity);
        $itemCount = $this->items->sum('quantity');

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
            
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'item_count' => $itemCount,
            
            'summary' => [
                'subtotal' => round($subtotal, 2),
                'subtotal_formatted' => '₦' . number_format($subtotal, 2),
                'discount' => 0, 
                'discount_formatted' => '₦0.00',
                'shipping' => 0, 
                'shipping_formatted' => '₦0.00',
                'tax' => 0,
                'tax_formatted' => '₦0.00',
                'total' => round($subtotal, 2),
                'total_formatted' => '₦' . number_format($subtotal, 2),
            ],
            
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
