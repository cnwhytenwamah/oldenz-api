<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'sort_order',
        string $sortOrder = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator;
    public function findBySlug(string $slug): ?Model;
    public function getParentCategoriesWithChildren(): Collection;
    public function getFeatured(int $limit = 10): Collection;
    public function getActive(): Collection;
    public function getChildrenByParentId(int $parentId): Collection;
    public function getWithProductsCount(): Collection;
    public function reorder(array $order): bool;
}