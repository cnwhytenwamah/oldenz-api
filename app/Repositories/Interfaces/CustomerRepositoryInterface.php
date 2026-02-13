<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;
    public function findByEmail(string $email): ?Model;
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
    public function getTopBySpending(int $limit = 10): Collection;
    public function getRecent(int $limit = 10): Collection;
    public function getVIPCustomers(): Collection;
    public function getActiveCustomers(): Collection;
    public function getInactiveCustomers(): Collection;
    public function getNewCustomers(int $days = 30): Collection;
    public function getForExport(array $filters = []): Collection;
    public function countByDateRange(Carbon $startDate, Carbon $endDate): int;
    public function getActiveCustomersCount(Carbon $startDate, Carbon $endDate): int;
}
