<?php

namespace App\Services\Frontend;

use Exception;
use App\Dto\CartItemDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\Frontend\CustomerBaseService;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;


class CartService extends CustomerBaseService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Get or create cart for customer
     */
    public function getOrCreateCart(int $customerId): Model
    {
        $cart = $this->cartRepository->getActiveByCustomer($customerId);

        if (!$cart) {
            $cart = $this->cartRepository->create([
                'customer_id' => $customerId,
                'status' => 'active',
                'last_activity_at' => now(),
            ]);
        }

        return $cart->load(['items.product.images', 'items.productVariant']);
    }

    /**
     * Get cart with items
     */
    public function getCart(int $customerId): ?Model
    {
        $cart = $this->getOrCreateCart($customerId);

        $this->cartRepository->update($cart->id, [
            'last_activity_at' => now()
        ]);

        return $cart;
    }

    /**
     * Add item to cart
     */
    public function addItem(
        int $customerId,
        int $productId,
        int $quantity,
        ?int $variantId = null
    ): Model {
        try {
            DB::beginTransaction();

            $cart = $this->getOrCreateCart($customerId);
            $product = $this->productRepository->findOrFail($productId);

            if (!$product->is_active) {
                throw new Exception('Product is not available');
            }

            $price = $variantId 
                ? $product->variants()->findOrFail($variantId)->price ?? $product->price
                : $product->price;

            $existingItem = $cart->items()->where('product_id', $productId)->where('product_variant_id', $variantId)->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $quantity;
                
                $this->validateStock($product, $variantId, $newQuantity);
                
                $existingItem->update([
                    'quantity' => $newQuantity,
                    'price' => $price,
                ]);
            } else {
                $this->validateStock($product, $variantId, $quantity);

                $cartItemData = CartItemDto::fromRequest([
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                ], $cart->id, $price);

                $cart->items()->create($cartItemData->toArray());
            }

            DB::commit();

            return $cart->fresh(['items.product.images', 'items.productVariant']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add item to cart: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItemQuantity(int $customerId, int $itemId, int $quantity): Model
    {
        try {
            DB::beginTransaction();

            $cart = $this->getOrCreateCart($customerId);
            $item = $cart->items()->findOrFail($itemId);

            $this->validateStock($item->product, $item->product_variant_id, $quantity);

            $item->update(['quantity' => $quantity]);

            DB::commit();

            return $cart->fresh(['items.product.images', 'items.productVariant']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update cart item: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $customerId, int $itemId): Model
    {
        try {
            DB::beginTransaction();

            $cart = $this->getOrCreateCart($customerId);
            $cart->items()->findOrFail($itemId)->delete();

            DB::commit();

            return $cart->fresh(['items.product.images', 'items.productVariant']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove cart item: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clear cart
     */
    public function clearCart(int $customerId): bool
    {
        try {
            DB::beginTransaction();

            $cart = $this->getOrCreateCart($customerId);
            $cart->items()->delete();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to clear cart: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get cart summary
     */
    public function getCartSummary(int $customerId): array
    {
        $cart = $this->getCart($customerId);
        
        $subtotal = 0;
        $itemCount = 0;

        foreach ($cart->items as $item) {
            $subtotal += $item->price * $item->quantity;
            $itemCount += $item->quantity;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'item_count' => $itemCount,
            'discount' => 0, 
            'shipping' => 0, 
            'tax' => 0,
            'total' => round($subtotal, 2),
        ];
    }

    /**
     * Validate product stock
     */
    private function validateStock(Model $product, ?int $variantId, int $quantity): void
    {
        if ($variantId) {
            $variant = $product->variants()->findOrFail($variantId);
            
            if ($variant->stock_quantity < $quantity) {
                throw new Exception("Only {$variant->stock_quantity} items available in stock");
            }
        } else {
            if ($product->track_inventory && $product->stock_quantity < $quantity) {
                throw new Exception("Only {$product->stock_quantity} items available in stock");
            }
        }
    }

    /**
     * Merge guest cart to customer cart
     */
    public function mergeGuestCart(int $customerId, string $sessionId): Model
    {
        try {
            DB::beginTransaction();

            $guestCart = $this->cartRepository->getBySessionId($sessionId);
            $customerCart = $this->getOrCreateCart($customerId);

            if ($guestCart && $guestCart->items->count() > 0) {
                foreach ($guestCart->items as $item) {
                    $existingItem = $customerCart->items()->where('product_id', $item->product_id)->where('product_variant_id', $item->product_variant_id)->first();

                    if ($existingItem) {
                        $existingItem->update([
                            'quantity' => $existingItem->quantity + $item->quantity
                        ]);
                    } else {
                        $item->update(['cart_id' => $customerCart->id]);
                    }
                }

                $guestCart->delete();
            }

            DB::commit();

            return $customerCart->fresh(['items.product.images', 'items.productVariant']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to merge guest cart: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark cart as abandoned
     */
    public function markAsAbandoned(): int
    {
        $cutoffTime = now()->subHours(24);
        
        return $this->cartRepository->markAbandonedCarts($cutoffTime);
    }
}
