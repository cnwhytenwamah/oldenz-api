<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_customer',
        'usage_count',
        'starts_at',
        'expires_at',
        'is_active',
        'applicable_categories',
        'applicable_products',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'usage_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
    ];

    /**
     * Get orders that used this promo code
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'promo_code_id');
    }

    /**
     * Check if promo code is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if promo code has expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return true;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return true;
        }

        return false;
    }

    /**
     * Check if promo code is applicable to a product
     */
    public function isApplicableToProduct(int $productId): bool
    {
        // If no specific products are set, it applies to all
        if (!$this->applicable_products || empty($this->applicable_products)) {
            return true;
        }

        return in_array($productId, $this->applicable_products);
    }

    /**
     * Check if promo code is applicable to a category
     */
    public function isApplicableToCategory(int $categoryId): bool
    {
        if (!$this->applicable_categories || empty($this->applicable_categories)) {
            return true;
        }

        return in_array($categoryId, $this->applicable_categories);
    }

    /**
     * Calculate discount amount for given order total
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = ($orderAmount * $this->discount_value) / 100;

            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }

            return $discount;
        }

        return min($this->discount_value, $orderAmount);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get remaining uses
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null;
        }

        return max(0, $this->usage_limit - $this->usage_count);
    }

    /**
     * Scope a query to only include active promo codes
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query->where('is_active', true)
                     ->where(function ($q) use ($now) {
                         $q->whereNull('starts_at')
                           ->orWhere('starts_at', '<=', $now);
                     })
                     ->where(function ($q) use ($now) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>=', $now);
                     });
    }

    /**
     * Scope a query to only include expired promo codes
     */
    public function scopeExpired($query)
    {
        $now = now();

        return $query->where(function ($q) use ($now) {
            $q->where('expires_at', '<', $now)
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('usage_limit')
                       ->whereColumn('usage_count', '>=', 'usage_limit');
              });
        });
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($promoCode) {
            // Ensure code is uppercase
            $promoCode->code = strtoupper($promoCode->code);
        });
    }
}
