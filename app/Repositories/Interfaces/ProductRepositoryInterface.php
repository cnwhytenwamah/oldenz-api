<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;
    public function findBySlug(string $slug): ?Model;
    public function findBySku(string $sku): ?Model;
    public function getFeatured(int $limit = 10): Collection;
    public function getNewArrivals(int $limit = 10): Collection;
    public function getBestSellers(int $limit = 10): Collection;
    public function getOnSale(int $limit = 10): Collection;
    public function getRelatedProducts(int $productId, int $limit = 4): Collection;
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;
    public function updateStock(int $productId, int $quantity): bool;
    public function decrementStock(int $productId, int $quantity): bool;
    public function incrementStock(int $productId, int $quantity): bool;
    public function getLowStock(): Collection;
    public function getOutOfStock(): Collection;
}
