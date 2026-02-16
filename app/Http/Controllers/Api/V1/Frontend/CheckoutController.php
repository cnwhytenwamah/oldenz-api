<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\BaseController;
use App\Services\Frontend\CheckoutService;
use App\Http\Requests\Frontend\CheckoutRequest;

class CheckoutController extends BaseController
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
    }

    /**
     * Calculate order totals (for checkout preview)
     */
    public function calculateTotals(Request $request): JsonResponse
    {
        $request->validate([
            'promo_code' => ['nullable', 'string'],
            'shipping' => ['nullable', 'array'],
        ]);

        try {
            $totals = $this->checkoutService->calculateOrderTotals(
                customerId: $request->user()->id,
                promoCode: $request->promo_code,
                shippingDetails: $request->shipping
            );

            return response()->json([
                'data' => $totals,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Process checkout and create order
     */
    public function process(CheckoutRequest $request): JsonResponse
    {
        try {
            $orderData = OrderDto::fromRequest(
                $request->validated(),
                $request->user()->id,
                $request->user()->email
            );

            $result = $this->checkoutService->processCheckout(
                $orderData,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Order created successfully',
                'order' => new OrderResource($result['order']),
                'payment' => $result['payment'],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(string $reference): JsonResponse
    {
        try {
            $result = $this->checkoutService->verifyPayment($reference);

            if ($result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'order' => new OrderResource($result['order']),
                ]);
            }

            return response()->json([
                'message' => $result['message'],
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle payment callback (webhook)
     */
    public function paymentCallback(Request $request): JsonResponse
    {
        
        $reference = $request->reference ?? $request->transaction_id;

        if (!$reference) {
            return response()->json([
                'message' => 'Invalid payment callback',
            ], 400);
        }

        try {
            $result = $this->checkoutService->verifyPayment($reference);

            return response()->json([
                'message' => 'Payment processed',
                'success' => $result['success'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

