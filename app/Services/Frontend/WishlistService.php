<?php

namespace App\Services\Frontend;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Frontend\CustomerBaseService;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\CustomerRepositoryInterface;


class WishlistService extends CustomerBaseService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Get customer wishlist
     */
    public function getWishlist(int $customerId): Collection
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        
        return $customer->wishlist()->with(['images', 'categories'])->where('is_active', true)->get();
    }

    /**
     * Add product to wishlist
     */
    public function addToWishlist(int $customerId, int $productId): bool
    {
        try {
            DB::beginTransaction();

            $customer = $this->customerRepository->findOrFail($customerId);
            $product = $this->productRepository->findOrFail($productId);

            if (!$product->is_active) {
                throw new Exception('Product is not available');
            }

            if ($customer->wishlist()->where('product_id', $productId)->exists()) {
                throw new Exception('Product already in wishlist');
            }

            $customer->wishlist()->attach($productId);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add to wishlist: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist(int $customerId, int $productId): bool
    {
        try {
            DB::beginTransaction();

            $customer = $this->customerRepository->findOrFail($customerId);
            $customer->wishlist()->detach($productId);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove from wishlist: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if product is in wishlist
     */
    public function isInWishlist(int $customerId, int $productId): bool
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        
        return $customer->wishlist()->where('product_id', $productId)->exists();
    }

    /**
     * Clear wishlist
     */
    public function clearWishlist(int $customerId): bool
    {
        try {
            DB::beginTransaction();

            $customer = $this->customerRepository->findOrFail($customerId);
            $customer->wishlist()->detach();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to clear wishlist: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get wishlist count
     */
    public function getWishlistCount(int $customerId): int
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        
        return $customer->wishlist()->where('is_active', true)->count();
    }

    /**
     * Move wishlist items to cart
     */
    public function moveToCart(int $customerId, array $productIds): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($productIds as $productId) {
            try {
                $this->removeFromWishlist($customerId, $productId);
                $results['success'][] = $productId;
            } catch (Exception $e) {
                $results['failed'][] = [
                    'product_id' => $productId,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
