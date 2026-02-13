<?php

namespace App\Repositories\Eloquents;

use Carbon\Carbon;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\OrderRepositoryInterface;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    /**
     * Get orders with filters and sorting
     */
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query()->with(['customer', 'items', 'payment']);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                  ->orWhere('customer_email', 'like', "%{$filters['search']}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($filters) {
                      $customerQuery->where('first_name', 'like', "%{$filters['search']}%")
                                   ->orWhere('last_name', 'like', "%{$filters['search']}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['fulfillment_status'])) {
            $query->where('fulfillment_status', $filters['fulfillment_status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['min_total'])) {
            $query->where('total', '>=', $filters['min_total']);
        }

        if (!empty($filters['max_total'])) {
            $query->where('total', '<=', $filters['max_total']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?Model
    {
        return $this->model->with(['customer', 'items.product', 'payment'])->where('order_number', $orderNumber)->first();
    }

    /**
     * Get orders by customer
     */
    public function getByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['items.product', 'payment'])->where('customer_id', $customerId)->latest()->paginate($perPage);
    }

    /**
     * Get recent orders
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['customer', 'items'])->latest()->limit($limit)->get();
    }

    /**
     * Count orders by status
     */
    public function countByStatus(OrderStatus|string $status): int
    {
        $statusValue = $status instanceof OrderStatus ? $status->value : $status;
        
        return $this->model->where('status', $statusValue)->count();
    }

    /**
     * Count orders by date range
     */
    public function countByDateRange(Carbon $startDate, Carbon $endDate): int
    {
        return $this->model->whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return (float) $this->model->where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->sum('total');
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValue(Carbon $startDate, Carbon $endDate): float
    {
        return (float) $this->model->where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->avg('total');
    }

    /**
     * Get orders for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->query()->with(['customer', 'payment']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->get();
    }

    /**
     * Get pending confirmation orders
     */
    public function getPendingConfirmation(): Collection
    {
        return $this->model->with(['customer'])->where('status', OrderStatus::PENDING->value)->where('payment_status', 'paid')->latest()->get();
    }

    /**
     * Get pending shipment orders
     */
    public function getPendingShipment(): Collection
    {
        return $this->model->with(['customer'])->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::PROCESSING->value])->whereNull('shipped_at')->latest()->get();
    }

    /**
     * Get payment failed orders
     */
    public function getPaymentFailed(): Collection
    {
        return $this->model->with(['customer', 'payment'])->where('payment_status', 'failed')->latest()->get();
    }
}