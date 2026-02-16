<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\BaseController;
use App\Services\Admin\AdminOrderService;

// use App\Http\Requests\Admin\UpdateOrderRequest;


class AdminOrderController extends BaseController
{
    public function __construct(
        protected AdminOrderService $orderService
    ) {
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getAllOrders(
            filters: $request->all(),
            sortBy: $request->input('sort_by', 'created_at'),
            sortOrder: $request->input('sort_order', 'desc'),
            perPage: $request->input('per_page', 15)
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
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

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
     * Update order status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $updated = $this->orderService->updateOrderStatus(
            $id,
            $request->status,
            $request->note
        );

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update order status',
            ], 500);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
        ]);
    }

    /**
     * Update shipping information
     */
    public function updateShipping(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tracking_number' => ['required', 'string'],
            'carrier' => ['required', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $updated = $this->orderService->updateShippingInfo(
            $id,
            $request->tracking_number,
            $request->carrier,
            $request->status
        );

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update shipping information',
            ], 500);
        }

        return response()->json([
            'message' => 'Shipping information updated successfully',
        ]);
    }

    /**
     * Add admin note to order
     */
    public function addNote(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'note' => ['required', 'string'],
        ]);

        $updated = $this->orderService->addAdminNote($id, $request->note);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to add note',
            ], 500);
        }

        return response()->json([
            'message' => 'Note added successfully',
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string'],
        ]);

        try {
            $cancelled = $this->orderService->cancelOrder($id, $request->reason);

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
     * Process refund
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string'],
        ]);

        try {
            $refunded = $this->orderService->processRefund(
                $id,
                $request->amount,
                $request->reason
            );

            if (!$refunded) {
                return response()->json([
                    'message' => 'Failed to process refund',
                ], 500);
            }

            return response()->json([
                'message' => 'Refund processed successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->orderService->getOrderStatistics($request->all());

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Export orders
     */
    public function export(Request $request): JsonResponse
    {
        $csv = $this->orderService->exportOrders($request->all());

        return response()->json([
            'data' => $csv,
        ]);
    }

    /**
     * Get orders needing attention
     */
    public function needsAttention(): JsonResponse
    {
        $orders = $this->orderService->getOrdersNeedingAttention();

        return response()->json([
            'data' => [
                'pending_confirmation' => OrderResource::collection($orders['pending_confirmation']),
                'pending_shipment' => OrderResource::collection($orders['pending_shipment']),
                'payment_failed' => OrderResource::collection($orders['payment_failed']),
            ],
        ]);
    }
}

