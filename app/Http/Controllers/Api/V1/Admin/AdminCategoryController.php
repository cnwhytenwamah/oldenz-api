<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Exception;
use App\Dto\CategoryDto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\BaseController;
use App\Http\Resources\CategoryResource;
use App\Services\Admin\AdminCategoryService;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;


class AdminCategoryController extends BaseController
{
    public function __construct(
        protected AdminCategoryService $categoryService
    ) {
    }

    /**
     * Display a listing of categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories(
            filters: $request->all(),
            sortBy: $request->input('sort_by', 'sort_order'),
            sortOrder: $request->input('sort_order', 'asc'),
            perPage: $request->input('per_page', 15)
        );

        return response()->json([
            'data' => CategoryResource::collection($categories->items()),
            'meta' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }

    /**
     * Get category tree
     */
    public function tree(): JsonResponse
    {
        $categories = $this->categoryService->getCategoryTree();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $categoryData = CategoryDto::fromRequest($request->validated());
        $category = $this->categoryService->createCategory($categoryData);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $categoryData = CategoryDto::fromRequest($request->validated());
        $updated = $this->categoryService->updateCategory($id, $categoryData);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update category',
            ], 500);
        }

        $category = $this->categoryService->getCategoryById($id);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->categoryService->deleteCategory($id);

            if (!$deleted) {
                return response()->json([
                    'message' => 'Failed to delete category',
                ], 500);
            }

            return response()->json([
                'message' => 'Category deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle category status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $updated = $this->categoryService->toggleCategoryStatus($id);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update category status',
            ], 500);
        }

        return response()->json([
            'message' => 'Category status updated successfully',
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'integer', 'exists:categories,id'],
            'categories.*.sort_order' => ['required', 'integer'],
        ]);

        $updated = $this->categoryService->reorderCategories($request->categories);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to reorder categories',
            ], 500);
        }

        return response()->json([
            'message' => 'Categories reordered successfully',
        ]);
    }

    /**
     * Get category statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $stats = $this->categoryService->getCategoryStatistics($id);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
