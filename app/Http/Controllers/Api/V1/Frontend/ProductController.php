<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductCollection;
use App\Services\Frontend\ProductService;


class ProductController extends BaseController
{
    public function __construct(
        protected ProductService $productService
    ) {
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request): ProductCollection
    {
        $products = $this->productService->getProducts(
            filters: $request->all(),
            sortBy: $request->input('sort_by', 'created_at'),
            sortOrder: $request->input('sort_order', 'desc'),
            perPage: $request->input('per_page', 15)
        );

        return new ProductCollection($products);
    }

    /**
     * Display the specified product by slug
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

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
     * Search products
     */
    public function search(Request $request): ProductCollection
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
        ]);

        $products = $this->productService->searchProducts(
            $request->query,
            $request->input('per_page', 15)
        );

        return new ProductCollection($products);
    }

    /**
     * Get featured products
     */
    public function featured(Request $request): JsonResponse
    {
        $products = $this->productService->getFeaturedProducts(
            $request->input('limit', 10)
        );

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Get new arrival products
     */
    public function newArrivals(Request $request): JsonResponse
    {
        $products = $this->productService->getNewArrivals(
            $request->input('limit', 10)
        );

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Get best seller products
     */
    public function bestSellers(Request $request): JsonResponse
    {
        $products = $this->productService->getBestSellers(
            $request->input('limit', 10)
        );

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Get products on sale
     */
    public function onSale(Request $request): JsonResponse
    {
        $products = $this->productService->getOnSaleProducts(
            $request->input('limit', 10)
        );

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Get related products
     */
    public function related(Request $request, int $id): JsonResponse
    {
        $products = $this->productService->getRelatedProducts(
            $id,
            $request->input('limit', 4)
        );

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Get products by category
     */
    public function byCategory(Request $request, int $categoryId): ProductCollection
    {
        $products = $this->productService->getProductsByCategory(
            $categoryId,
            $request->input('per_page', 15)
        );

        return new ProductCollection($products);
    }

    /**
     * Check product availability
     */
    public function checkAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $availability = $this->productService->checkAvailability(
            $id,
            $request->variant_id,
            $request->input('quantity', 1)
        );

        return response()->json([
            'data' => $availability,
        ]);
    }

    /**
     * Get product filters (for filter UI)
     */
    public function filters(Request $request): JsonResponse
    {
        $filters = $this->productService->getProductFilters(
            $request->category_id
        );

        return response()->json([
            'data' => $filters,
        ]);
    }
}

