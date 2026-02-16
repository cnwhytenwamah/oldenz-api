<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Services\Frontend\OrderService;
use App\Http\Controllers\BaseController;

class OrderController extends BaseController
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * Display a listing of customer's orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getCustomerOrders(
            $request->user()->id,
            $request->input('per_page', 15)
        );

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById(
            $id,
            $request->user()->id
        );

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Track order by order number
     */
    public function track(Request $request, string $orderNumber): JsonResponse
    {
        $tracking = $this->orderService->trackOrder(
            $orderNumber,
            $request->user()->id
        );

        if (!$tracking) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'data' => $tracking,
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $cancelled = $this->orderService->cancelOrder(
                $id,
                $request->user()->id,
                $request->reason
            );

            if (!$cancelled) {
                return response()->json([
                    'message' => 'Failed to cancel order',
                ], 500);
            }

            return response()->json([
                'message' => 'Order cancelled successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add customer note to order
     */
    public function addNote(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        try {
            $updated = $this->orderService->addCustomerNote(
                $id,
                $request->user()->id,
                $request->note
            );

            if (!$updated) {
                return response()->json([
                    'message' => 'Failed to add note',
                ], 500);
            }

            return response()->json([
                'message' => 'Note added successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
