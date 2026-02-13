<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\CategoryRepositoryInterface;


class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * Get categories with filters and sorting
     */
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'sort_order',
        string $sortOrder = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query()->with(['parent', 'children']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?Model
    {
        return $this->model->with(['parent', 'children', 'products'])->where('slug', $slug)->first();
    }

    /**
     * Get parent categories with their children
     */
    public function getParentCategoriesWithChildren(): Collection
    {
        return $this->model->with('children')->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get featured categories
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return $this->model->where('is_featured', true)->where('is_active', true)->orderBy('sort_order')->limit($limit)->get();
    }

    /**
     * Get active categories
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get child categories by parent ID
     */
    public function getChildrenByParentId(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get category with products count
     */
    public function getWithProductsCount(): Collection
    {
        return $this->model->withCount('products')->orderBy('sort_order')->get();
    }

    /**
     * Reorder categories
     */
    public function reorder(array $order): bool
    {
        try {
            foreach ($order as $item) {
                $this->update($item['id'], ['sort_order' => $item['sort_order']]);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
