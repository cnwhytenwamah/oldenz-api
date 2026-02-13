<?php

namespace App\Dto;

use Carbon\Carbon;

readonly class PromoCodeDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public string $code,
        public ?string $description,
        public string $discountType,
        public float $discountValue,
        public ?float $minOrderAmount,
        public ?float $maxDiscountAmount,
        public ?int $usageLimit,
        public ?int $usageLimitPerCustomer,
        public int $usageCount,
        public ?Carbon $startsAt,
        public ?Carbon $expiresAt,
        public bool $isActive,
        public ?array $applicableCategories,
        public ?array $applicableProducts,
    ) {  }

    /**
     * Create from request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            code: strtoupper($data['code']),
            description: $data['description'] ?? null,
            discountType: $data['discount_type'],
            discountValue: (float) $data['discount_value'],
            minOrderAmount: isset($data['min_order_amount']) ? (float) $data['min_order_amount'] : null,
            maxDiscountAmount: isset($data['max_discount_amount']) ? (float) $data['max_discount_amount'] : null,
            usageLimit: isset($data['usage_limit']) ? (int) $data['usage_limit'] : null,
            usageLimitPerCustomer: isset($data['usage_limit_per_customer']) ? (int) $data['usage_limit_per_customer'] : null,
            usageCount: (int) ($data['usage_count'] ?? 0),
            startsAt: isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            isActive: (bool) ($data['is_active'] ?? true),
            applicableCategories: $data['applicable_categories'] ?? null,
            applicableProducts: $data['applicable_products'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'min_order_amount' => $this->minOrderAmount,
            'max_discount_amount' => $this->maxDiscountAmount,
            'usage_limit' => $this->usageLimit,
            'usage_limit_per_customer' => $this->usageLimitPerCustomer,
            'usage_count' => $this->usageCount,
            'starts_at' => $this->startsAt,
            'expires_at' => $this->expiresAt,
            'is_active' => $this->isActive,
            'applicable_categories' => $this->applicableCategories,
            'applicable_products' => $this->applicableProducts,
        ];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->discountType === 'percentage') {
            $discount = ($orderAmount * $this->discountValue) / 100;
            
            // Apply max discount cap if set
            if ($this->maxDiscountAmount !== null && $discount > $this->maxDiscountAmount) {
                $discount = $this->maxDiscountAmount;
            }
            
            return $discount;
        }

        // Fixed amount
        return min($this->discountValue, $orderAmount);
    }

    /**
     * Check if promo code is valid
     */
    public function isValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = Carbon::now();

        // Check start date
        if ($this->startsAt && $now->isBefore($this->startsAt)) {
            return false;
        }

        // Check expiry date
        if ($this->expiresAt && $now->isAfter($this->expiresAt)) {
            return false;
        }

        // Check usage limit
        if ($this->usageLimit !== null && $this->usageCount >= $this->usageLimit) {
            return false;
        }

        return true;
    }

    /**
     * Check if order meets minimum amount requirement
     */
    public function meetsMinimumAmount(float $orderAmount): bool
    {
        if ($this->minOrderAmount === null) {
            return true;
        }

        return $orderAmount >= $this->minOrderAmount;
    }

    /**
     * Check if promo code is applicable to product
     */
    public function isApplicableToProduct(int $productId): bool
    {
        // If no specific products are set, it applies to all
        if ($this->applicableProducts === null || empty($this->applicableProducts)) {
            return true;
        }

        return in_array($productId, $this->applicableProducts);
    }

    /**
     * Check if promo code is applicable to category
     */
    public function isApplicableToCategory(int $categoryId): bool
    {
        // If no specific categories are set, it applies to all
        if ($this->applicableCategories === null || empty($this->applicableCategories)) {
            return true;
        }

        return in_array($categoryId, $this->applicableCategories);
    }
}
