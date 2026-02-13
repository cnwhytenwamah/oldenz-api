<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get orders with filters and sorting
     */
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;
    public function findByOrderNumber(string $orderNumber): ?Model;
    public function getByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator;
    public function getRecent(int $limit = 10): Collection;
    public function countByStatus(OrderStatus|string $status): int;
    public function countByDateRange(Carbon $startDate, Carbon $endDate): int;
    public function getTotalRevenue(Carbon $startDate, Carbon $endDate): float;
    public function getAverageOrderValue(Carbon $startDate, Carbon $endDate): float;
    public function getForExport(array $filters = []): Collection;
    public function getPendingConfirmation(): Collection;
    public function getPendingShipment(): Collection;
    public function getPaymentFailed(): Collection;
}

