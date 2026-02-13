<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $publicData = [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'min_order_amount' => $this->min_order_amount,
            'min_order_amount_formatted' => $this->min_order_amount 
                ? '₦' . number_format($this->min_order_amount, 2) 
                : null,
        ];

        $adminData = $this->when(
            $request->user() instanceof \App\Models\Admin,
            [
                'max_discount_amount' => $this->max_discount_amount,
                'usage_limit' => $this->usage_limit,
                'usage_limit_per_customer' => $this->usage_limit_per_customer,
                'usage_count' => $this->usage_count,
                'remaining_uses' => $this->remaining_uses,
                'applicable_categories' => $this->applicable_categories,
                'applicable_products' => $this->applicable_products,
                'is_active' => $this->is_active,
            ]
        );

        return array_merge($publicData, $adminData ?? [], [
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ]);
    }
}
