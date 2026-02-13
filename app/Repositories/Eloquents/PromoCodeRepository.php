<?php

namespace App\Repositories\Eloquents;

use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Eloquents\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\PromoCodeRepositoryInterface;

class PromoCodeRepository extends BaseRepository implements PromoCodeRepositoryInterface
{
    public function __construct(PromoCode $model)
    {
        parent::__construct($model);
    }

    /**
     * Get promo codes with filters
     */
    public function getWithFilters(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('code', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        if (!empty($filters['status'])) {
            $now = now();
            
            switch ($filters['status']) {
                case 'active':
                    $query->where('is_active', true)
                          ->where(function ($q) use ($now) {
                              $q->whereNull('starts_at')
                                ->orWhere('starts_at', '<=', $now);
                          })
                          ->where(function ($q) use ($now) {
                              $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>=', $now);
                          });
                    break;
                case 'expired':
                    $query->where('expires_at', '<', $now);
                    break;
                case 'scheduled':
                    $query->where('starts_at', '>', $now);
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find promo code by code
     */
    public function findByCode(string $code): ?Model
    {
        return $this->model->where('code', strtoupper($code))->first();
    }

    /**
     * Get active promo codes
     */
    public function getActive(): Collection
    {
        $now = now();
        
        return $this->model
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                      ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->get();
    }

    /**
     * Get expired promo codes
     */
    public function getExpired(): Collection
    {
        return $this->model
            ->where('expires_at', '<', now())
            ->orWhere(function ($query) {
                $query->whereNotNull('usage_limit')
                      ->whereColumn('usage_count', '>=', 'usage_limit');
            })
            ->get();
    }

    /**
     * Get customer usage count
     */
    public function getCustomerUsageCount(int $promoCodeId, int $customerId): int
    {
        return DB::table('orders')->where('promo_code_id', $promoCodeId)->where('customer_id', $customerId)->count();
    }

    /**
     * Get promo codes for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->get();
    }
}
