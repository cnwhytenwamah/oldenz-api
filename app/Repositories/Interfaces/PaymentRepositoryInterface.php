<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    public function findByReference(string $reference): ?Model;
    public function findByGatewayReference(string $gatewayReference): ?Model;
    public function getByOrder(int $orderId): Collection;
}
