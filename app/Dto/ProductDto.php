<?php

namespace App\Dto;

use Str;
use App\Dto\BaseDto;

readonly class ProductDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $slug,
        public string $sku,
        public ?string $description,
        public ?string $shortDescription,
        public float $price,
        public ?float $compareAtPrice,
        public ?float $costPrice,
        public int $stockQuantity,
        public int $lowStockThreshold,
        public string $stockStatus,
        public bool $trackInventory,
        public ?string $brand,
        public ?string $gender,
        public ?array $colors,
        public ?array $sizes,
        public ?array $materials,
        public bool $isActive,
        public bool $isFeatured,
        public bool $isNewArrival,
        public bool $isBestSeller,
        public bool $isOnSale,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?array $metaKeywords,
        public ?array $categoryIds,
        public ?array $images,
    ) {
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            slug: $data['slug'] ?? Str::slug($data['name']),
            sku: $data['sku'],
            description: $data['description'] ?? null,
            shortDescription: $data['short_description'] ?? null,
            price: (float) $data['price'],
            compareAtPrice: isset($data['compare_at_price']) ? (float) $data['compare_at_price'] : null,
            costPrice: isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            stockQuantity: (int) ($data['stock_quantity'] ?? 0),
            lowStockThreshold: (int) ($data['low_stock_threshold'] ?? 10),
            stockStatus: $data['stock_status'] ?? 'in_stock',
            trackInventory: (bool) ($data['track_inventory'] ?? true),
            brand: $data['brand'] ?? null,
            gender: $data['gender'] ?? null,
            colors: $data['colors'] ?? null,
            sizes: $data['sizes'] ?? null,
            materials: $data['materials'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
            isFeatured: (bool) ($data['is_featured'] ?? false),
            isNewArrival: (bool) ($data['is_new_arrival'] ?? false),
            isBestSeller: (bool) ($data['is_best_seller'] ?? false),
            isOnSale: (bool) ($data['is_on_sale'] ?? false),
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            metaKeywords: $data['meta_keywords'] ?? null,
            categoryIds: $data['category_ids'] ?? null,
            images: $data['images'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->shortDescription,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'cost_price' => $this->costPrice,
            'stock_quantity' => $this->stockQuantity,
            'low_stock_threshold' => $this->lowStockThreshold,
            'stock_status' => $this->stockStatus,
            'track_inventory' => $this->trackInventory,
            'brand' => $this->brand,
            'gender' => $this->gender,
            'colors' => $this->colors,
            'sizes' => $this->sizes,
            'materials' => $this->materials,
            'is_active' => $this->isActive,
            'is_featured' => $this->isFeatured,
            'is_new_arrival' => $this->isNewArrival,
            'is_best_seller' => $this->isBestSeller,
            'is_on_sale' => $this->isOnSale,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'meta_keywords' => $this->metaKeywords,
        ];
    }
}

