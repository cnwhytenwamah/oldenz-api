<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\BaseController;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCollection;
use App\Services\Frontend\ProductService;
use App\Services\Admin\AdminCategoryService;

class CategoryController extends BaseController
{
    public function __construct(
        protected AdminCategoryService $categoryService,
        protected ProductService $productService
    ) {
    }

    /**
     * Display a listing of categories (tree structure)
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getCategoryTree();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Display the specified category by slug
     */
    public function show(string $slug): JsonResponse
    {
        $category = $this->categoryService->getCategoryBySlug($slug);

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
     * Get featured categories
     */
    public function featured(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getFeaturedCategories(
            $request->input('limit', 10)
        );

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get products in a category
     */
    public function products(Request $request, string $slug): JsonResponse|ProductCollection
    {
        $category = $this->categoryService->getCategoryBySlug($slug);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $products = $this->productService->getProductsByCategory(
            $category->id,
            $request->input('per_page', 15)
        );

        return new ProductCollection($products);
    }
}



