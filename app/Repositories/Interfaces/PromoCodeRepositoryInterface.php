<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface PromoCodeRepositoryInterface extends BaseRepositoryInterface
{
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;
    public function findByCode(string $code): ?Model;
    public function getActive(): Collection;
    public function getExpired(): Collection;
    public function getCustomerUsageCount(int $promoCodeId, int $customerId): int;
    public function getForExport(array $filters = []): Collection;
}
