<?php

namespace App\Services\Frontend;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Services\Frontend\CustomerBaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\OrderRepositoryInterface;


class OrderService extends CustomerBaseService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * Get customer orders
     */
    public function getCustomerOrders(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByCustomer($customerId, $perPage);
    }

    /**
     * Get order by ID (for current customer only)
     */
    public function getOrderById(int $orderId, int $customerId): ?Model
    {
        $order = $this->orderRepository->find($orderId, ['*'], ['items.product', 'payment']);

        if ($order && $order->customer_id === $customerId) {
            return $order;
        }

        return null;
    }

    /**
     * Get order by order number (for current customer only)
     */
    public function getOrderByNumber(string $orderNumber, int $customerId): ?Model
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);

        if ($order && $order->customer_id === $customerId) {
            return $order->load(['items.product', 'payment']);
        }

        return null;
    }

    /**
     * Track order
     */
    public function trackOrder(string $orderNumber, int $customerId): ?array
    {
        $order = $this->getOrderByNumber($orderNumber, $customerId);

        if (!$order) {
            return null;
        }

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'tracking_number' => $order->tracking_number,
            'carrier' => $order->carrier,
            'confirmed_at' => $order->confirmed_at,
            'shipped_at' => $order->shipped_at,
            'delivered_at' => $order->delivered_at,
            'timeline' => $this->getOrderTimeline($order),
        ];
    }

    /**
     * Get order timeline
     */
    private function getOrderTimeline(Model $order): array
    {
        $timeline = [
            [
                'status' => 'placed',
                'label' => 'Order Placed',
                'completed' => true,
                'date' => $order->created_at,
            ],
        ];

        if ($order->confirmed_at) {
            $timeline[] = [
                'status' => 'confirmed',
                'label' => 'Order Confirmed',
                'completed' => true,
                'date' => $order->confirmed_at,
            ];
        }

        if ($order->shipped_at) {
            $timeline[] = [
                'status' => 'shipped',
                'label' => 'Shipped',
                'completed' => true,
                'date' => $order->shipped_at,
            ];
        }

        if ($order->delivered_at) {
            $timeline[] = [
                'status' => 'delivered',
                'label' => 'Delivered',
                'completed' => true,
                'date' => $order->delivered_at,
            ];
        }

        return $timeline;
    }

    /**
     * Cancel order (if cancellable)
     */
    public function cancelOrder(int $orderId, int $customerId, string $reason): bool
    {
        $order = $this->getOrderById($orderId, $customerId);

        if (!$order) {
            throw new Exception('Order not found');
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new Exception('Order cannot be cancelled');
        }

        return $this->orderRepository->update($orderId, [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'customer_note' => $reason,
        ]);
    }

    /**
     * Add customer note to order
     */
    public function addCustomerNote(int $orderId, int $customerId, string $note): bool
    {
        $order = $this->getOrderById($orderId, $customerId);

        if (!$order) {
            throw new Exception('Order not found');
        }

        return $this->orderRepository->update($orderId, [
            'customer_note' => $note
        ]);
    }
}
