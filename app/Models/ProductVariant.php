<?php

namespace App\Models;

use App\Models\Product;
use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'color',
        'size',
        'material',
        'attributes',
        'price',
        'compare_at_price',
        'stock_quantity',
        'stock_status',
        'image_url',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'stock_status' => StockStatus::class,
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get effective price (variant price or product price)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->price ?? $this->product->price;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        $comparePrice = $this->compare_at_price ?? $this->product->compare_at_price;
        $price = $this->getEffectivePriceAttribute();

        if ($comparePrice && $comparePrice > $price) {
            return round((($comparePrice - $price) / $comparePrice) * 100);
        }

        return null;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_status === StockStatus::IN_STOCK;
    }

    /**
     * Check if variant is low in stock
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= 10 && $this->stock_quantity > 0;
    }

    /**
     * Scope to get active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get in stock variants
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', StockStatus::IN_STOCK)->where('stock_quantity', '>', 0);
    }
}
