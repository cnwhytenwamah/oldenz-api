<?php

namespace App\Services\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Frontend\CustomerBaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\ProductRepositoryInterface;


class ProductService extends CustomerBaseService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Get all active products with filters
     */
    public function getProducts(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $filters['is_active'] = true;

        return $this->productRepository->getWithFilters(
            $filters,
            $sortBy,
            $sortOrder,
            $perPage
        );
    }

    /**
     * Get product by slug
     */
    public function getProductBySlug(string $slug): ?Model
    {
        $product = $this->productRepository->findBySlug($slug);

        if ($product && $product->is_active) {
            $product->incrementViewCount();
            
            return $product;
        }

        return null;
    }

    /**
     * Search products
     */
    public function searchProducts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->search($query, $perPage);
    }

    /**
     * Get featured products
     */
    public function getFeaturedProducts(int $limit = 10): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    /**
     * Get new arrivals
     */
    public function getNewArrivals(int $limit = 10): Collection
    {
        return $this->productRepository->getNewArrivals($limit);
    }

    /**
     * Get best sellers
     */
    public function getBestSellers(int $limit = 10): Collection
    {
        return $this->productRepository->getBestSellers($limit);
    }

    /**
     * Get products on sale
     */
    public function getOnSaleProducts(int $limit = 10): Collection
    {
        return $this->productRepository->getOnSale($limit);
    }

    /**
     * Get related products
     */
    public function getRelatedProducts(int $productId, int $limit = 4): Collection
    {
        return $this->productRepository->getRelatedProducts($productId, $limit);
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getByCategory($categoryId, $perPage);
    }

    /**
     * Get recently viewed products
     */
    public function getRecentlyViewedProducts(array $productIds, int $limit = 5): Collection
    {
        return $this->productRepository->findWhereIn('id', $productIds, $limit);
    }

    /**
     * Check product availability
     */
    public function checkAvailability(int $productId, ?int $variantId = null, int $quantity = 1): array
    {
        $product = $this->productRepository->find($productId);

        if (!$product) {
            return [
                'available' => false,
                'message' => 'Product not found',
            ];
        }

        if (!$product->is_active) {
            return [
                'available' => false,
                'message' => 'Product is not available',
            ];
        }

        if ($variantId) {
            $variant = $product->variants()->find($variantId);
            
            if (!$variant) {
                return [
                    'available' => false,
                    'message' => 'Product variant not found',
                ];
            }

            if (!$variant->is_active) {
                return [
                    'available' => false,
                    'message' => 'Product variant is not available',
                ];
            }

            if ($variant->stock_quantity < $quantity) {
                return [
                    'available' => false,
                    'message' => 'Insufficient stock',
                    'available_quantity' => $variant->stock_quantity,
                ];
            }
        } else {
            if ($product->track_inventory && $product->stock_quantity < $quantity) {
                return [
                    'available' => false,
                    'message' => 'Insufficient stock',
                    'available_quantity' => $product->stock_quantity,
                ];
            }
        }

        return [
            'available' => true,
            'message' => 'Product is available',
        ];
    }

    /**
     * Get product filters data (for filter UI)
     */
    public function getProductFilters(?int $categoryId = null): array
    {
        $query = $this->productRepository->query()->where('is_active', true);

        if ($categoryId) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
        }

        return [
            'price_range' => [
                'min' => $query->min('price'),
                'max' => $query->max('price'),
            ],
            'brands' => $query->distinct()->pluck('brand')->filter()->sort()->values()->toArray(),
            'colors' => $query->whereNotNull('colors')->get()->pluck('colors')->flatten()->unique()->sort()->values()->toArray(),
            'sizes' => $query->whereNotNull('sizes')->get()->pluck('sizes')->flatten()->unique()->sort()->values()->toArray(),
            'genders' => $query->distinct()->whereNotNull('gender')->pluck('gender')->sort()->values()->toArray(),
        ];
    }
}
