<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface CartRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveByCustomer(int $customerId): ?Model;
    public function getBySessionId(string $sessionId): ?Model;
    public function markAbandonedCarts(Carbon $cutoffTime): int;
    public function deleteOldAbandonedCarts(Carbon $cutoffTime): int;
    public function getAbandonedForRecovery(Carbon $cutoffTime): Collection;
}
