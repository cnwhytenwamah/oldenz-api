<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'track_inventory',
        'brand',
        'gender',
        'colors',
        'sizes',
        'materials',
        'is_active',
        'is_featured',
        'is_new_arrival',
        'is_best_seller',
        'is_on_sale',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count',
        'order_count',
        'average_rating',
        'review_count',
        'published_at',
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
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'stock_status' => StockStatus::class,
            'track_inventory' => 'boolean',
            'colors' => 'array',
            'sizes' => 'array',
            'materials' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_on_sale' => 'boolean',
            'meta_keywords' => 'array',
            'view_count' => 'integer',
            'order_count' => 'integer',
            'average_rating' => 'decimal:2',
            'review_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Get the categories for the product
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')->withTimestamps();
    }

    /**
     * Get all images for the product
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the primary image
     */
    public function primaryImage(): HasMany
    {
        return $this->images()->where('is_primary', true);
    }

    /**
     * Get all variants for the product
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get active variants
     */
    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if ($this->track_inventory) {
            return $this->stock_quantity > 0;
        }
        return true;
    }

    /**
     * Check if product is low in stock
     */
    public function isLowStock(): bool
    {
        if ($this->track_inventory) {
            return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
        }
        return false;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->compare_at_price && $this->compare_at_price > $this->price) {
            return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
        }
        return null;
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Scope to get active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get new arrivals
     */
    public function scopeNewArrivals($query)
    {
        return $query->where('is_new_arrival', true);
    }

    /**
     * Scope to get best sellers
     */
    public function scopeBestSellers($query)
    {
        return $query->where('is_best_seller', true);
    }

    /**
     * Scope to get products on sale
     */
    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true);
    }

    /**
     * Scope to get in stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', StockStatus::IN_STOCK);
    }
}