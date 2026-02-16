<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CartResource;
use App\Services\Frontend\CartService;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Frontend\AddToCartRequest;
use App\Http\Requests\Frontend\UpdateCartItemRequest;



class CartController extends BaseController
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    /**
     * Display the customer's cart
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user()->id);

        return response()->json([
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Add item to cart
     */
    public function add(AddToCartRequest $request): JsonResponse
    {
        try {
            $cart = $this->cartService->addItem(
                customerId: $request->user()->id,
                productId: $request->product_id,
                quantity: $request->quantity,
                variantId: $request->product_variant_id
            );

            return response()->json([
                'message' => 'Item added to cart successfully',
                'data' => new CartResource($cart),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        try {
            $cart = $this->cartService->updateItemQuantity(
                $request->user()->id,
                $itemId,
                $request->quantity
            );

            return response()->json([
                'message' => 'Cart item updated successfully',
                'data' => new CartResource($cart),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request, int $itemId): JsonResponse
    {
        try {
            $cart = $this->cartService->removeItem(
                $request->user()->id,
                $itemId
            );

            return response()->json([
                'message' => 'Item removed from cart successfully',
                'data' => new CartResource($cart),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Clear cart
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $this->cartService->clearCart($request->user()->id);

            return response()->json([
                'message' => 'Cart cleared successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get cart summary
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->cartService->getCartSummary($request->user()->id);

        return response()->json([
            'data' => $summary,
        ]);
    }
}


