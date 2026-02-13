<?php

namespace App\Repositories\Eloquents;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use App\Repositories\Interfaces\PaymentRepositoryInterface;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * Find payment by transaction reference
     */
    public function findByReference(string $reference): ?Model
    {
        return $this->model->with(['order'])->where('transaction_reference', $reference)->first();
    }

    /**
     * Find payment by gateway reference
     */
    public function findByGatewayReference(string $gatewayReference): ?Model
    {
        return $this->model->with(['order'])->where('gateway_reference', $gatewayReference)->first();
    }

    /**
     * Get payments by order
     */
    public function getByOrder(int $orderId): Collection
    {
        return $this->model->where('order_id', $orderId)->latest()->get();
    }
}