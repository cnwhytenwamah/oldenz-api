<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductImageResource;
use App\Http\Resources\ProductVariantResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            
            // Pricing
            'price' => [
                'amount' => (float) $this->price,
                'formatted' => '₦' . number_format($this->price, 2),
                'compare_at_price' => $this->compare_at_price ? (float) $this->compare_at_price : null,
                'compare_at_formatted' => $this->compare_at_price ? '₦' . number_format($this->compare_at_price, 2) : null,
                'discount_percentage' => $this->discount_percentage,
                'on_sale' => $this->is_on_sale,
            ],
            
            // Inventory
            'inventory' => [
                'stock_quantity' => $this->stock_quantity,
                'low_stock_threshold' => $this->low_stock_threshold,
                'stock_status' => $this->stock_status,
                'is_in_stock' => $this->isInStock(),
                'is_low_stock' => $this->isLowStock(),
                'track_inventory' => $this->track_inventory,
            ],
            
            // Attributes
            'brand' => $this->brand,
            'gender' => $this->gender,
            'colors' => $this->colors,
            'sizes' => $this->sizes,
            'materials' => $this->materials,
            
            // Status flags
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_new_arrival' => $this->is_new_arrival,
            'is_best_seller' => $this->is_best_seller,
            'is_on_sale' => $this->is_on_sale,
            
            // SEO
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
            
            // Stats
            'stats' => [
                'view_count' => $this->view_count,
                'order_count' => $this->order_count,
                'average_rating' => (float) $this->average_rating,
                'review_count' => $this->review_count,
            ],
            
            // Relationships
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->whenLoaded('images', function () {
                $primary = $this->images->firstWhere('is_primary', true);
                return $primary ? new ProductImageResource($primary) : null;
            }),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            
            // Timestamps
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
            ],
        ];
    }
}