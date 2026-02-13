<?php

namespace App\Services\Frontend;

use Exception;
use App\Dto\OrderDto;
use App\Dto\PaymentDto;
use App\Dto\OrderItemDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Frontend\CartService;
use App\Services\Shared\PaymentService;
use Illuminate\Database\Eloquent\Model;
use App\Services\Shared\NotificationService;
use App\Services\Frontend\CustomerBaseService;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;


class CheckoutService extends CustomerBaseService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected PaymentRepositoryInterface $paymentRepository,
        protected ProductRepositoryInterface $productRepository,
        protected CartService $cartService,
        protected PaymentService $paymentService,
        protected NotificationService $notificationService
    ) { }

    /**
     * Process checkout and create order
     */
    public function processCheckout(OrderDto $orderData, int $customerId): array
    {
        try {
            DB::beginTransaction();

            $cart = $this->cartService->getCart($customerId);

            if ($cart->items->isEmpty()) {
                throw new Exception('Cart is empty');
            }

            $this->validateCartStock($cart);

            $order = $this->orderRepository->create($orderData->toArray());

            foreach ($cart->items as $cartItem) {
                $orderItemData = OrderItemDto::fromCartItem($cartItem, $order->id);
                $order->items()->create($orderItemData->toArray());

                $this->decrementStock($cartItem);
            }

            $paymentData = PaymentDto::fromRequest([
                'payment_gateway' => $orderData->paymentMethod ?? 'paystack',
                'payment_method' => 'card',
                'amount' => $order->total,
            ], $order->id);

            $payment = $this->paymentRepository->create($paymentData->toArray());

            $paymentAuth = $this->paymentService->initializePayment(
                $payment,
                $order,
                auth()->user()
            );

            $this->cartService->clearCart($customerId);

            $this->notificationService->sendOrderConfirmation(
                auth()->user(),
                $order
            );

            DB::commit();

            return [
                'order' => $order->fresh(['items.product', 'payment']),
                'payment' => $paymentAuth,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Checkout failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(
        int $customerId,
        ?string $promoCode = null,
        ?array $shippingDetails = null
    ): array {
        $cart = $this->cartService->getCart($customerId);
        
        if ($cart->items->isEmpty()) {
            throw new Exception('Cart is empty');
        }

        $subtotal = 0;
        foreach ($cart->items as $item) {
            $subtotal += $item->price * $item->quantity;
        }

        $discountAmount = 0;
        if ($promoCode) {
            $discountAmount = 0;
        }

        $shippingFee = $this->calculateShipping($cart, $shippingDetails);

        $taxAmount = 0;

        $total = $subtotal - $discountAmount + $shippingFee + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Validate stock for all cart items
     */
    private function validateCartStock(Model $cart): void
    {
        foreach ($cart->items as $item) {
            $product = $item->product;

            if ($item->product_variant_id) {
                $variant = $item->productVariant;
                
                if (!$variant || $variant->stock_quantity < $item->quantity) {
                    throw new Exception("Insufficient stock for {$product->name}");
                }
            } else {
                if ($product->track_inventory && $product->stock_quantity < $item->quantity) {
                    throw new Exception("Insufficient stock for {$product->name}");
                }
            }
        }
    }

    /**
     * Decrement product stock
     */
    private function decrementStock($cartItem): void
    {
        if ($cartItem->product_variant_id) {
            $variant = $cartItem->productVariant;
            $variant->decrement('stock_quantity', $cartItem->quantity);
            
            if ($variant->stock_quantity <= 0) {
                $variant->update(['stock_status' => 'out_of_stock']);
            }
        } else {
            $product = $cartItem->product;
            
            if ($product->track_inventory) {
                $this->productRepository->decrementStock(
                    $product->id,
                    $cartItem->quantity
                );
            }
        }
    }

    /**
     * Calculate shipping fee
     */
    private function calculateShipping(Model $cart, ?array $shippingDetails): float
    {
        
        if (!$shippingDetails) {
            return 0;
        }

        $state = $shippingDetails['state'] ?? null;

        $shippingRates = [
            'Lagos' => 2000,
            'Abuja' => 3000,
            'Port Harcourt' => 2500,
            // Add more states
        ];

        return $shippingRates[$state] ?? 3500;
    }

    /**
     * Verify payment and update order
     */
    public function verifyPayment(string $paymentReference): array
    {
        try {
            DB::beginTransaction();

            $verificationResult = $this->paymentService->verifyPayment($paymentReference);

            if (!$verificationResult['success']) {
                throw new Exception('Payment verification failed');
            }

            $payment = $this->paymentRepository->findByReference($paymentReference);
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }

            $this->paymentRepository->update($payment->id, [
                'status' => 'successful',
                'gateway_reference' => $verificationResult['gateway_reference'],
                'gateway_response' => $verificationResult['response'],
                'paid_at' => now(),
            ]);

            $order = $payment->order;
            $this->orderRepository->update($order->id, [
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $this->notificationService->sendPaymentConfirmation(
                $order->customer,
                $order
            );

            DB::commit();

            return [
                'success' => true,
                'order' => $order->fresh(['items.product', 'payment']),
                'message' => 'Payment successful',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment verification failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle failed payment
     */
    public function handleFailedPayment(string $paymentReference, string $reason): void
    {
        try {
            DB::beginTransaction();

            $payment = $this->paymentRepository->findByReference($paymentReference);
            
            if ($payment) {
                $this->paymentRepository->update($payment->id, [
                    'status' => 'failed',
                    'failed_at' => now(),
                ]);

                $order = $payment->order;
                $this->orderRepository->update($order->id, [
                    'payment_status' => 'failed',
                ]);

                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        $variant = $item->productVariant;
                        $variant->increment('stock_quantity', $item->quantity);
                    } else {
                        $this->productRepository->incrementStock(
                            $item->product_id,
                            $item->quantity
                        );
                    }
                }

                $this->notificationService->sendPaymentFailed(
                    $order->customer,
                    $order,
                    $reason
                );
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle payment failure: ' . $e->getMessage());
        }
    }
}
