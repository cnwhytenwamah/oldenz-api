<?php

namespace App\Dto;

use App\Dto\BaseDto;

readonly class ProductVariantDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public int $productId,
        public string $sku,
        public string $name,
        public ?string $color,
        public ?string $size,
        public ?string $material,
        public ?array $attributes,
        public ?float $price,
        public ?float $compareAtPrice,
        public int $stockQuantity,
        public string $stockStatus,
        public ?string $imageUrl,
        public bool $isActive,
    ) {
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $productId): self
    {
        return new self(
            id: $data['id'] ?? null,
            productId: $productId,
            sku: $data['sku'],
            name: $data['name'],
            color: $data['color'] ?? null,
            size: $data['size'] ?? null,
            material: $data['material'] ?? null,
            attributes: $data['attributes'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            compareAtPrice: isset($data['compare_at_price']) ? (float) $data['compare_at_price'] : null,
            stockQuantity: (int) ($data['stock_quantity'] ?? 0),
            stockStatus: $data['stock_status'] ?? 'in_stock',
            imageUrl: $data['image_url'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'color' => $this->color,
            'size' => $this->size,
            'material' => $this->material,
            'attributes' => $this->attributes,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'stock_quantity' => $this->stockQuantity,
            'stock_status' => $this->stockStatus,
            'image_url' => $this->imageUrl,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * Generate variant name from attributes
     */
    public static function generateName(?string $color, ?string $size, ?string $material): string
    {
        $parts = array_filter([$color, $size, $material]);
        return implode(' - ', $parts);
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->stockQuantity > 0 && $this->stockStatus === 'in_stock';
    }

    /**
     * Get effective price (variant price or product price)
     */
    public function getEffectivePrice(?float $productPrice): float
    {
        return $this->price ?? $productPrice ?? 0;
    }
}

