<?php

namespace App\Dto;

use App\Dto\BaseDto;

readonly class OrderItemDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public int $orderId,
        public int $productId,
        public ?int $productVariantId,
        public string $productName,
        public string $productSku,
        public ?string $variantName,
        public ?array $variantAttributes,
        public float $unitPrice,
        public int $quantity,
        public float $subtotal,
        public float $discountAmount,
        public float $total,
    ) {  }

    /**
     * Create from cart item
     */
    public static function fromCartItem($cartItem, int $orderId): self
    {
        $product = $cartItem->product;
        $variant = $cartItem->productVariant;

        return new self(
            id: null,
            orderId: $orderId,
            productId: $product->id,
            productVariantId: $variant?->id,
            productName: $product->name,
            productSku: $variant?->sku ?? $product->sku,
            variantName: $variant?->name,
            variantAttributes: $variant?->attributes,
            unitPrice: $cartItem->price,
            quantity: $cartItem->quantity,
            subtotal: $cartItem->price * $cartItem->quantity,
            discountAmount: 0,
            total: $cartItem->price * $cartItem->quantity,
        );
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $orderId): self
    {
        return new self(
            id: $data['id'] ?? null,
            orderId: $orderId,
            productId: $data['product_id'],
            productVariantId: $data['product_variant_id'] ?? null,
            productName: $data['product_name'],
            productSku: $data['product_sku'],
            variantName: $data['variant_name'] ?? null,
            variantAttributes: $data['variant_attributes'] ?? null,
            unitPrice: (float) $data['unit_price'],
            quantity: (int) $data['quantity'],
            subtotal: (float) $data['subtotal'],
            discountAmount: (float) ($data['discount_amount'] ?? 0),
            total: (float) $data['total'],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'variant_name' => $this->variantName,
            'variant_attributes' => $this->variantAttributes,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'total' => $this->total,
        ];
    }

    /**
     * Calculate subtotal
     */
    public static function calculateSubtotal(float $unitPrice, int $quantity): float
    {
        return $unitPrice * $quantity;
    }

    /**
     * Calculate total after discount
     */
    public static function calculateTotal(float $subtotal, float $discountAmount = 0): float
    {
        return $subtotal - $discountAmount;
    }
}