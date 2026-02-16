<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Dto\ProductDto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductCollection;
use App\Services\Frontend\ProductService;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;


class AdminProductController extends BaseController
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request): ProductCollection
    {
        $products = $this->productService->getAllProducts(
            filters: $request->all(),
            sortBy: $request->input('sort_by', 'created_at'),
            sortOrder: $request->input('sort_order', 'desc'),
            perPage: $request->input('per_page', 15)
        );

        return new ProductCollection($products);
    }

    /**
     * Store a newly created product
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $productData = ProductDto::fromRequest($request->validated());
        $product = $this->productService->createProduct($productData);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $productData = ProductDto::fromRequest($request->validated());
        $updated = $this->productService->updateProduct($id, $productData);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update product',
            ], 500);
        }

        $product = $this->productService->getProductById($id);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->productService->deleteProduct($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Failed to delete product',
            ], 500);
        }

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Toggle product active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $updated = $this->productService->toggleProductStatus($id);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update product status',
            ], 500);
        }

        return response()->json([
            'message' => 'Product status updated successfully',
        ]);
    }

    /**
     * Update product stock
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $updated = $this->productService->updateStock($id, $request->stock_quantity);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update stock',
            ], 500);
        }

        return response()->json([
            'message' => 'Stock updated successfully',
        ]);
    }

    /**
     * Duplicate product
     */
    public function duplicate(int $id): JsonResponse
    {
        $product = $this->productService->duplicateProduct($id);

        if (!$product) {
            return response()->json([
                'message' => 'Failed to duplicate product',
            ], 500);
        }

        return response()->json([
            'message' => 'Product duplicated successfully',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'data' => ['required', 'array'],
        ]);

        $updated = $this->productService->bulkUpdate(
            $request->product_ids,
            $request->data
        );

        return response()->json([
            'message' => "{$updated} products updated successfully",
        ]);
    }
}

