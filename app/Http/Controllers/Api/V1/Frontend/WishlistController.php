<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use App\Http\Controllers\BaseController;
use App\Services\Frontend\WishlistService;

class WishlistController extends BaseController
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {
    }

    /**
     * Display customer's wishlist
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->wishlistService->getWishlist($request->user()->id);

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        try {
            $this->wishlistService->addToWishlist(
                $request->user()->id,
                $request->product_id
            );

            return response()->json([
                'message' => 'Product added to wishlist successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove product from wishlist
     */
    public function remove(Request $request, int $productId): JsonResponse
    {
        try {
            $this->wishlistService->removeFromWishlist(
                $request->user()->id,
                $productId
            );

            return response()->json([
                'message' => 'Product removed from wishlist successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Clear wishlist
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $this->wishlistService->clearWishlist($request->user()->id);

            return response()->json([
                'message' => 'Wishlist cleared successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if product is in wishlist
     */
    public function check(Request $request, int $productId): JsonResponse
    {
        $inWishlist = $this->wishlistService->isInWishlist(
            $request->user()->id,
            $productId
        );

        return response()->json([
            'in_wishlist' => $inWishlist,
        ]);
    }

    /**
     * Get wishlist count
     */
    public function count(Request $request): JsonResponse
    {
        $count = $this->wishlistService->getWishlistCount($request->user()->id);

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Move wishlist items to cart
     */
    public function moveToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        try {
            $results = $this->wishlistService->moveToCart(
                $request->user()->id,
                $request->product_ids
            );

            return response()->json([
                'message' => 'Items processed',
                'data' => $results,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle product in wishlist
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        try {
            $inWishlist = $this->wishlistService->isInWishlist(
                $request->user()->id,
                $request->product_id
            );

            if ($inWishlist) {
                $this->wishlistService->removeFromWishlist(
                    $request->user()->id,
                    $request->product_id
                );

                return response()->json([
                    'message' => 'Product removed from wishlist',
                    'in_wishlist' => false,
                ]);
            } else {
                $this->wishlistService->addToWishlist(
                    $request->user()->id,
                    $request->product_id
                );

                return response()->json([
                    'message' => 'Product added to wishlist',
                    'in_wishlist' => true,
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}