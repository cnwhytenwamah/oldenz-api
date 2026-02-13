<?php

namespace App\Services\Admin;

use Exception;
use App\Dto\PromoCodeDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\PromoCodeRepositoryInterface;


class PromoCodeService extends AdminBaseService
{
    public function __construct(
        protected PromoCodeRepositoryInterface $promoCodeRepository
    ) {
    }

    /**
     * Get all promo codes with filters
     */
    public function getAllPromoCodes(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->promoCodeRepository->getWithFilters(
            $filters,
            $sortBy,
            $sortOrder,
            $perPage
        );
    }

    /**
     * Get promo code by ID
     */
    public function getPromoCodeById(int $id): ?Model
    {
        return $this->promoCodeRepository->find($id);
    }

    /**
     * Get promo code by code
     */
    public function getPromoCodeByCode(string $code): ?Model
    {
        return $this->promoCodeRepository->findByCode($code);
    }

    /**
     * Create a new promo code
     */
    public function createPromoCode(PromoCodeDto $data): Model
    {
        try {
            DB::beginTransaction();

            if ($this->promoCodeRepository->findByCode($data->code)) {
                throw new Exception('Promo code already exists');
            }

            $promoCode = $this->promoCodeRepository->create($data->toArray());

            DB::commit();

            return $promoCode;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create promo code: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing promo code
     */
    public function updatePromoCode(int $id, PromoCodeData $data): bool
    {
        try {
            DB::beginTransaction();

            $existing = $this->promoCodeRepository->findByCode($data->code);
            if ($existing && $existing->id !== $id) {
                throw new Exception('Promo code already exists');
            }

            $updated = $this->promoCodeRepository->update($id, $data->toArray());

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update promo code: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a promo code
     */
    public function deletePromoCode(int $id): bool
    {
        try {
            DB::beginTransaction();

            $deleted = $this->promoCodeRepository->delete($id);

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete promo code: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle promo code active status
     */
    public function togglePromoCodeStatus(int $id): bool
    {
        $promoCode = $this->promoCodeRepository->findOrFail($id);
        
        return $this->promoCodeRepository->update($id, [
            'is_active' => !$promoCode->is_active
        ]);
    }

    /**
     * Validate promo code for order
     */
    public function validatePromoCode(
        string $code,
        int $customerId,
        float $orderAmount,
        array $productIds = [],
        array $categoryIds = []
    ): array {
        $promoCode = $this->promoCodeRepository->findByCode($code);

        if (!$promoCode) {
            return [
                'valid' => false,
                'message' => 'Invalid promo code',
            ];
        }

        $promoData = PromoCodeDto::fromRequest($promoCode->toArray());

        if (!$promoData->isValid()) {
            return [
                'valid' => false,
                'message' => 'Promo code is expired or inactive',
            ];
        }

        if (!$promoData->meetsMinimumAmount($orderAmount)) {
            return [
                'valid' => false,
                'message' => "Minimum order amount of ₦" . number_format($promoData->minOrderAmount, 2) . " required",
            ];
        }

        if ($promoData->usageLimitPerCustomer) {
            $customerUsage = $this->promoCodeRepository->getCustomerUsageCount($promoCode->id, $customerId);
            
            if ($customerUsage >= $promoData->usageLimitPerCustomer) {
                return [
                    'valid' => false,
                    'message' => 'You have reached the usage limit for this promo code',
                ];
            }
        }

        if ($productIds && $promoData->applicableProducts) {
            $hasApplicableProduct = false;
            foreach ($productIds as $productId) {
                if ($promoData->isApplicableToProduct($productId)) {
                    $hasApplicableProduct = true;
                    break;
                }
            }

            if (!$hasApplicableProduct) {
                return [
                    'valid' => false,
                    'message' => 'Promo code is not applicable to products in your cart',
                ];
            }
        }

        if ($categoryIds && $promoData->applicableCategories) {
            $hasApplicableCategory = false;
            foreach ($categoryIds as $categoryId) {
                if ($promoData->isApplicableToCategory($categoryId)) {
                    $hasApplicableCategory = true;
                    break;
                }
            }

            if (!$hasApplicableCategory) {
                return [
                    'valid' => false,
                    'message' => 'Promo code is not applicable to products in your cart',
                ];
            }
        }

        $discountAmount = $promoData->calculateDiscount($orderAmount);

        return [
            'valid' => true,
            'promo_code_id' => $promoCode->id,
            'code' => $promoCode->code,
            'discount_amount' => $discountAmount,
            'message' => 'Promo code applied successfully',
        ];
    }

    /**
     * Apply promo code to order (increment usage count)
     */
    public function applyPromoCode(int $promoCodeId): void
    {
        $promoCode = $this->promoCodeRepository->findOrFail($promoCodeId);
        
        $this->promoCodeRepository->update($promoCodeId, [
            'usage_count' => $promoCode->usage_count + 1
        ]);
    }

    /**
     * Get promo code statistics
     */
    public function getPromoCodeStatistics(int $id): array
    {
        $promoCode = $this->promoCodeRepository->findOrFail($id);

        $totalDiscount = DB::table('orders')
            ->where('promo_code_id', $id)
            ->sum('discount_amount');

        return [
            'usage_count' => $promoCode->usage_count,
            'total_discount_given' => round($totalDiscount, 2),
            'remaining_uses' => $promoCode->usage_limit 
                ? max(0, $promoCode->usage_limit - $promoCode->usage_count)
                : null,
        ];
    }

    /**
     * Get active promo codes
     */
    public function getActivePromoCodes(): Collection
    {
        return $this->promoCodeRepository->getActive();
    }

    /**
     * Get expired promo codes
     */
    public function getExpiredPromoCodes(): Collection
    {
        return $this->promoCodeRepository->getExpired();
    }

    /**
     * Generate unique promo code
     */
    public function generateUniqueCode(string $prefix = '', int $length = 8): string
    {
        do {
            $code = $prefix . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while ($this->promoCodeRepository->findByCode($code));

        return $code;
    }

    /**
     * Bulk create promo codes
     */
    public function bulkCreatePromoCodes(
        int $count,
        PromoCodeDto $template,
        string $prefix = 'PROMO'
    ): array {
        try {
            DB::beginTransaction();

            $promoCodes = [];

            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateUniqueCode($prefix);
                
                $promoData = PromoCodeDto::fromRequest([
                    ...$template->toArray(),
                    'code' => $code,
                ]);

                $promoCodes[] = $this->promoCodeRepository->create($promoData->toArray());
            }

            DB::commit();

            return $promoCodes;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk create promo codes: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export promo codes to CSV
     */
    public function exportPromoCodes(array $filters = []): string
    {
        $promoCodes = $this->promoCodeRepository->getForExport($filters);
        
        $csv = "Code,Description,Type,Value,Min Order,Usage,Limit,Status,Expires At\n";
        
        foreach ($promoCodes as $promoCode) {
            $csv .= sprintf(
                "%s,%s,%s,%.2f,%.2f,%d,%s,%s,%s\n",
                $promoCode->code,
                $promoCode->description ?? 'N/A',
                $promoCode->discount_type,
                $promoCode->discount_value,
                $promoCode->min_order_amount ?? 0,
                $promoCode->usage_count,
                $promoCode->usage_limit ?? 'Unlimited',
                $promoCode->is_active ? 'Active' : 'Inactive',
                $promoCode->expires_at ? $promoCode->expires_at->format('Y-m-d') : 'Never'
            );
        }

        return $csv;
    }
}
