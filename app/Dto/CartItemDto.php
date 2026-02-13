<?php

namespace App\Dto;

use App\Dto\BaseDto;


readonly class CartItemDto extends BaseDto
{
    public function __construct(
        public  ?int $id,
        public  int $cartId,
        public  int $productId,
        public  ?int $productVariantId,
        public  int $quantity,
        public  float $price,
    ) {
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $cartId, float $price): self
    {
        return new self(
            id: $data['id'] ?? null,
            cartId: $cartId,
            productId: $data['product_id'],
            productVariantId: $data['product_variant_id'] ?? null,
            quantity: (int) $data['quantity'],
            price: $price,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'cart_id' => $this->cartId,
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];
    }

    /**
     * Get subtotal
     */
    public function getSubtotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Update quantity
     */
    public function withQuantity(int $quantity): self
    {
        return new self(
            id: $this->id,
            cartId: $this->cartId,
            productId: $this->productId,
            productVariantId: $this->productVariantId,
            quantity: $quantity,
            price: $this->price,
        );
    }
}
