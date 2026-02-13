<?php

namespace App\Repositories\Eloquents;

use Carbon\Carbon;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\CustomerRepositoryInterface;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Get customers with filters and sorting
     */
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query()->withCount('orders');

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', "%{$filters['search']}%")
                  ->orWhere('last_name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Model
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Search customers
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })->paginate($perPage);
    }

    /**
     * Get top customers by spending
     */
    public function getTopBySpending(int $limit = 10): Collection
    {
        return $this->model
            ->select('customers.*')
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(total)')
                      ->from('orders')
                      ->whereColumn('orders.customer_id', 'customers.id')
                      ->where('orders.payment_status', 'paid');
            }, 'total_spent')->having('total_spent', '>', 0)->orderByDesc('total_spent')->limit($limit)->get();
    }

    /**
     * Get recent customers
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get VIP customers (high spending)
     */
    public function getVIPCustomers(): Collection
    {
        return $this->model
            ->select('customers.*')
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(total)')
                      ->from('orders')
                      ->whereColumn('orders.customer_id', 'customers.id')
                      ->where('orders.payment_status', 'paid');
            }, 'total_spent')
            ->having('total_spent', '>=', 100000)
            ->orderByDesc('total_spent')->get();
    }

    /**
     * Get active customers (recent purchases)
     */
    public function getActiveCustomers(): Collection
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        return $this->model
            ->whereHas('orders', function ($query) use ($thirtyDaysAgo) {
                $query->where('created_at', '>=', $thirtyDaysAgo);
            })->get();
    }

    /**
     * Get inactive customers (no recent purchases)
     */
    public function getInactiveCustomers(): Collection
    {
        $ninetyDaysAgo = now()->subDays(90);
        
        return $this->model
            ->whereDoesntHave('orders', function ($query) use ($ninetyDaysAgo) {
                $query->where('created_at', '>=', $ninetyDaysAgo);
            })
            ->orWhereHas('orders', function ($query) use ($ninetyDaysAgo) {
                $query->where('created_at', '<', $ninetyDaysAgo);
            })->get();
    }

    /**
     * Get new customers (registered recently)
     */
    public function getNewCustomers(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))->latest()->get();
    }

    /**
     * Get customers for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->query()
            ->withCount('orders')
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('SUM(total)')
                         ->from('orders')
                         ->whereColumn('orders.customer_id', 'customers.id')
                         ->where('orders.payment_status', 'paid');
            }, 'total_spent');

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
     * Count customers by date range
     */
    public function countByDateRange(Carbon $startDate, Carbon $endDate): int
    {
        return $this->model->whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Get active customers count
     */
    public function getActiveCustomersCount(Carbon $startDate, Carbon $endDate): int
    {
        return $this->model
            ->whereHas('orders', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->distinct()->count();
    }
}
