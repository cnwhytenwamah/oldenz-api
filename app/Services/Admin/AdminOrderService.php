<?php

namespace App\Services\Admin;

use Exception;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\Admin\AdminBaseService;
use App\Services\Shared\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\OrderRepositoryInterface;


class AdminOrderService extends AdminBaseService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Get all orders with filters
     */
    public function getAllOrders(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->orderRepository->getWithFilters(
            $filters,
            $sortBy,
            $sortOrder,
            $perPage
        );
    }

    /**
     * Get order by ID
     */
    public function getOrderById(int $id): ?Model
    {
        return $this->orderRepository->find($id, ['*'], ['customer', 'items.product', 'payment']);
    }

    /**
     * Get order by order number
     */
    public function getOrderByNumber(string $orderNumber): ?Model
    {
        return $this->orderRepository->findByOrderNumber($orderNumber);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(int $id, string $status, ?string $note = null): bool
    {
        try {
            DB::beginTransaction();

            $order = $this->orderRepository->findOrFail($id);
            
            $data = ['status' => $status];
            
            if ($note) {
                $data['admin_note'] = $note;
            }

            switch ($status) {
                case OrderStatus::CONFIRMED->value:
                    $data['confirmed_at'] = now();
                    break;
                case OrderStatus::SHIPPED->value:
                    $data['shipped_at'] = now();
                    break;
                case OrderStatus::DELIVERED->value:
                    $data['delivered_at'] = now();
                    break;
                case OrderStatus::CANCELLED->value:
                    $data['cancelled_at'] = now();
                    $this->restoreStock($order);
                    break;
            }

            $updated = $this->orderRepository->update($id, $data);

            if ($updated) {
                $this->notificationService->sendOrderStatusUpdate($order->customer, $order, $status);
            }

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order status: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update shipping information
     */
    public function updateShippingInfo(
        int $id,
        string $trackingNumber,
        string $carrier,
        ?string $status = null
    ): bool {
        try {
            DB::beginTransaction();

            $data = [
                'tracking_number' => $trackingNumber,
                'carrier' => $carrier,
            ];

            if ($status) {
                $data['status'] = $status;
                if ($status === OrderStatus::SHIPPED->value) {
                    $data['shipped_at'] = now();
                }
            }

            $updated = $this->orderRepository->update($id, $data);

            if ($updated) {
                $order = $this->orderRepository->findOrFail($id);
                $this->notificationService->sendShippingUpdate($order->customer, $order);
            }

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update shipping info: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add admin note to order
     */
    public function addAdminNote(int $id, string $note): bool
    {
        return $this->orderRepository->update($id, [
            'admin_note' => $note
        ]);
    }

    /**
     * Get order statistics
     */
    public function getOrderStatistics(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth();
        $endDate = $filters['end_date'] ?? now()->endOfMonth();

        return [
            'total_orders' => $this->orderRepository->countByDateRange($startDate, $endDate),
            'pending_orders' => $this->orderRepository->countByStatus(OrderStatus::PENDING),
            'confirmed_orders' => $this->orderRepository->countByStatus(OrderStatus::CONFIRMED),
            'processing_orders' => $this->orderRepository->countByStatus(OrderStatus::PROCESSING),
            'shipped_orders' => $this->orderRepository->countByStatus(OrderStatus::SHIPPED),
            'delivered_orders' => $this->orderRepository->countByStatus(OrderStatus::DELIVERED),
            'cancelled_orders' => $this->orderRepository->countByStatus(OrderStatus::CANCELLED),
            'total_revenue' => $this->orderRepository->getTotalRevenue($startDate, $endDate),
            'average_order_value' => $this->orderRepository->getAverageOrderValue($startDate, $endDate),
        ];
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 10)
    {
        return $this->orderRepository->getRecent($limit);
    }

    /**
     * Get orders by customer
     */
    public function getOrdersByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByCustomer($customerId, $perPage);
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $id, string $reason): bool
    {
        try {
            DB::beginTransaction();

            $order = $this->orderRepository->findOrFail($id);
            if (!in_array($order->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED])) {
                throw new Exception('Order cannot be cancelled in current status');
            }

            $this->orderRepository->update($id, [
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
                'admin_note' => $reason,
            ]);

            $this->restoreStock($order);
            $this->notificationService->sendOrderCancellation($order->customer, $order, $reason);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process refund
     */
    public function processRefund(int $id, float $amount, string $reason): bool
    {
        try {
            DB::beginTransaction();

            $order = $this->orderRepository->findOrFail($id);

            if (!$order->payment) {
                throw new Exception('No payment found for this order');
            }

            $order->payment->update([
                'status' => PaymentStatus::REFUNDED,
                'refunded_at' => now(),
            ]);

            $this->orderRepository->update($id, [
                'status' => OrderStatus::REFUNDED,
                'payment_status' => PaymentStatus::REFUNDED,
                'admin_note' => "Refund: {$reason}",
            ]);

            $this->restoreStock($order);

            $this->notificationService->sendRefundConfirmation($order->customer, $order, $amount);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to process refund: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restore product stock after cancellation
     */
    private function restoreStock(Model $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            
            if ($product->track_inventory) {
                $product->increment('stock_quantity', $item->quantity);

                if ($product->stock_quantity > 0 && $product->stock_status === 'out_of_stock') {
                    $product->update(['stock_status' => 'in_stock']);
                }
            }
        }
    }

    /**
     * Export orders to CSV
     */
    public function exportOrders(array $filters = []): string
    {
        $orders = $this->orderRepository->getForExport($filters);
        
        $csv = "Order Number,Customer,Email,Status,Payment Status,Total,Date\n";
        
        foreach ($orders as $order) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%.2f,%s\n",
                $order->order_number,
                $order->customer->full_name,
                $order->customer_email,
                $order->status->label(),
                $order->payment_status->label(),
                $order->total,
                $order->created_at->format('Y-m-d H:i:s')
            );
        }

        return $csv;
    }

    /**
     * Get orders needing attention
     */
    public function getOrdersNeedingAttention(): array
    {
        return [
            'pending_confirmation' => $this->orderRepository->getPendingConfirmation(),
            'pending_shipment' => $this->orderRepository->getPendingShipment(),
            'payment_failed' => $this->orderRepository->getPaymentFailed(),
        ];
    }
}

