<?php

namespace App\Repositories\Eloquents;

use Carbon\Carbon;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use App\Repositories\Interfaces\CartRepositoryInterface;

class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }

    /**
     * Get active cart by customer
     */
    public function getActiveByCustomer(int $customerId): ?Model
    {
        return $this->model->with(['items.product.images', 'items.productVariant'])->where('customer_id', $customerId)->where('status', 'active')->first();
    }

    /**
     * Get cart by session ID (for guest users)
     */
    public function getBySessionId(string $sessionId): ?Model
    {
        return $this->model->with(['items.product.images', 'items.productVariant'])->where('session_id', $sessionId)->where('status', 'active')->first();
    }

    /**
     * Mark abandoned carts
     */
    public function markAbandonedCarts(Carbon $cutoffTime): int
    {
        return $this->model->where('status', 'active')->where('last_activity_at', '<', $cutoffTime)->whereHas('items')->update(['status' => 'abandoned']);
    }

    /**
     * Delete old abandoned carts
     */
    public function deleteOldAbandonedCarts(Carbon $cutoffTime): int
    {
        $carts = $this->model->where('status', 'abandoned')->where('updated_at', '<', $cutoffTime)->get();

        $count = $carts->count();

        foreach ($carts as $cart) {
            $cart->items()->delete();
            $cart->delete();
        }

        return $count;
    }

    /**
     * Get abandoned carts for recovery email
     */
    public function getAbandonedForRecovery(Carbon $cutoffTime): Collection
    {
        return $this->model->with(['customer', 'items.product'])->where('status', 'abandoned')->where('last_activity_at', '>=', $cutoffTime)->whereNotNull('customer_id')->whereHas('items')->get();
    }
}
