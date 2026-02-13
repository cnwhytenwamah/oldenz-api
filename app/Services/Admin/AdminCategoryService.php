<?php

namespace App\Services\Admin;

use Exception;
use App\Dto\CategoryDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\Admin\AdminBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\CategoryRepositoryInterface;


class AdminCategoryService extends AdminBaseService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) { }

    /**
     * Get all categories with filters
     */
    public function getAllCategories(
        array $filters = [],
        string $sortBy = 'sort_order',
        string $sortOrder = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->categoryRepository->getWithFilters(
            $filters,
            $sortBy,
            $sortOrder,
            $perPage
        );
    }

    /**
     * Get category tree (hierarchical structure)
     */
    public function getCategoryTree(): Collection
    {
        return $this->categoryRepository->getParentCategoriesWithChildren();
    }

    /**
     * Get category by ID
     */
    public function getCategoryById(int $id): ?Model
    {
        return $this->categoryRepository->find($id, ['*'], ['parent', 'children', 'products']);
    }

    /**
     * Get category by slug
     */
    public function getCategoryBySlug(string $slug): ?Model
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    /**
     * Create a new category
     */
    public function createCategory(CategoryDto $data): Model
    {
        try {
            DB::beginTransaction();

            if ($data->parentId) {
                $parent = $this->categoryRepository->find($data->parentId);
                if (!$parent) {
                    throw new Exception('Parent category not found');
                }
            }

            $category = $this->categoryRepository->create($data->toArray());

            DB::commit();

            return $category->load(['parent', 'children']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing category
     */
    public function updateCategory(int $id, CategoryDto $data): bool
    {
        try {
            DB::beginTransaction();

            $category = $this->categoryRepository->findOrFail($id);

            if ($data->parentId && $this->wouldCreateCircularReference($id, $data->parentId)) {
                throw new Exception('Cannot set parent category - would create circular reference');
            }

            $updated = $this->categoryRepository->update($id, $data->toArray());

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a category
     */
    public function deleteCategory(int $id): bool
    {
        try {
            DB::beginTransaction();

            $category = $this->categoryRepository->findOrFail($id);

            if ($category->children()->count() > 0) {
                throw new Exception('Cannot delete category with child categories');
            }

            if ($category->products()->count() > 0) {
                throw new Exception('Cannot delete category with associated products');
            }

            $deleted = $this->categoryRepository->delete($id);

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle category active status
     */
    public function toggleCategoryStatus(int $id): bool
    {
        $category = $this->categoryRepository->findOrFail($id);
        
        return $this->categoryRepository->update($id, [
            'is_active' => !$category->is_active
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorderCategories(array $categoryOrders): bool
    {
        try {
            DB::beginTransaction();

            foreach ($categoryOrders as $order) {
                $this->categoryRepository->update($order['id'], [
                    'sort_order' => $order['sort_order']
                ]);
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder categories: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Move category to different parent
     */
    public function moveCategoryToParent(int $categoryId, ?int $newParentId): bool
    {
        try {
            DB::beginTransaction();

            if ($newParentId && $this->wouldCreateCircularReference($categoryId, $newParentId)) {
                throw new Exception('Cannot move category - would create circular reference');
            }

            $updated = $this->categoryRepository->update($categoryId, [
                'parent_id' => $newParentId
            ]);

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to move category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get featured categories
     */
    public function getFeaturedCategories(int $limit = 10): Collection
    {
        return $this->categoryRepository->getFeatured($limit);
    }

    /**
     * Check if setting parent would create circular reference
     */
    private function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        $parent = $this->categoryRepository->find($parentId);
        
        while ($parent) {
            if ($parent->id === $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Get category statistics
     */
    public function getCategoryStatistics(int $id): array
    {
        $category = $this->categoryRepository->findOrFail($id);

        return [
            'total_products' => $category->products()->count(),
            'active_products' => $category->products()->where('is_active', true)->count(),
            'total_children' => $category->children()->count(),
            'total_descendants' => $this->countDescendants($category),
        ];
    }

    /**
     * Count all descendants recursively
     */
    private function countDescendants(Model $category): int
    {
        $count = $category->children()->count();
        
        foreach ($category->children as $child) {
            $count += $this->countDescendants($child);
        }

        return $count;
    }
}

