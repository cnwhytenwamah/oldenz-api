<?php

namespace App\Repositories\Eloquents;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\ProductRepositoryInterface;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Get products with filters and sorting
     */
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query()->with(['categories', 'images']);

        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%")
                  ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['is_new_arrival'])) {
            $query->where('is_new_arrival', $filters['is_new_arrival']);
        }

        if (isset($filters['is_best_seller'])) {
            $query->where('is_best_seller', $filters['is_best_seller']);
        }

        if (isset($filters['is_on_sale'])) {
            $query->where('is_on_sale', $filters['is_on_sale']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find product by slug
     */
    public function findBySlug(string $slug): ?Model
    {
        return $this->model->with(['categories', 'images', 'variants'])->where('slug', $slug)->first();
    }

    /**
     * Find product by SKU
     */
    public function findBySku(string $sku): ?Model
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Get featured products
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return $this->model->with(['images', 'categories'])->where('is_featured', true)->where('is_active', true)->limit($limit)->get();
    }

    /**
     * Get new arrivals
     */
    public function getNewArrivals(int $limit = 10): Collection
    {
        return $this->model->with(['images', 'categories'])->where('is_new_arrival', true)->where('is_active', true)->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get best sellers
     */
    public function getBestSellers(int $limit = 10): Collection
    {
        return $this->model->with(['images', 'categories'])->where('is_best_seller', true)->where('is_active', true)->orderBy('order_count', 'desc')->limit($limit)->get();
    }

    /**
     * Get products on sale
     */
    public function getOnSale(int $limit = 10): Collection
    {
        return $this->model->with(['images', 'categories'])->where('is_on_sale', true)->where('is_active', true)->limit($limit)->get();
    }

    /**
     * Get related products
     */
    public function getRelatedProducts(int $productId, int $limit = 4): Collection
    {
        $product = $this->find($productId);
        
        if (!$product) {
            return collect();
        }

        $categoryIds = $product->categories->pluck('id')->toArray();

        return $this->model
            ->with(['images', 'categories'])
            ->where('id', '!=', $productId)
            ->where('is_active', true)
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })->inRandomOrder()->limit($limit)->get();
    }

    /**
     * Search products
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['images', 'categories'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('brand', 'like', "%{$query}%");
            })->paginate($perPage);
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['images', 'categories'])
            ->where('is_active', true)
            ->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            })->paginate($perPage);
    }

    /**
     * Update stock quantity
     */
    public function updateStock(int $productId, int $quantity): bool
    {
        $product = $this->find($productId);
        
        if (!$product) {
            return false;
        }

        return $product->update(['stock_quantity' => $quantity]);
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $productId, int $quantity): bool
    {
        $product = $this->find($productId);
        
        if (!$product || $product->stock_quantity < $quantity) {
            return false;
        }

        $product->decrement('stock_quantity', $quantity);
        
        if ($product->stock_quantity <= 0) {
            $product->update(['stock_status' => 'out_of_stock']);
        }

        return true;
    }

    /**
     * Increment stock
     */
    public function incrementStock(int $productId, int $quantity): bool
    {
        $product = $this->find($productId);
        
        if (!$product) {
            return false;
        }

        $product->increment('stock_quantity', $quantity);
 
        if ($product->stock_quantity > 0 && $product->stock_status === 'out_of_stock') {
            $product->update(['stock_status' => 'in_stock']);
        }

        return true;
    }

    /**
     * Get low stock products
     */
    public function getLowStock(): Collection
    {
        return $this->model->where('track_inventory', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0)->get();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStock(): Collection
    {
        return $this->model->where('track_inventory', true)->where('stock_quantity', '<=', 0)->orWhere('stock_status', 'out_of_stock')->get();
    }
}
