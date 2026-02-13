<?php

namespace App\Services\Shared;

use Exception;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;

// use Illuminate\Support\Facades\Mail;

class NotificationService extends BaseService
{
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($customer, $order): void
    {
        try {            
            Log::info("Order confirmation email sent to {$customer->email} for order {$order->order_number}");
            
            // Mail::to($customer->email)->send(new OrderConfirmation($order));
        } catch (Exception $e) {
            Log::error("Failed to send order confirmation: " . $e->getMessage());
        }
    }

    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($customer, $order): void
    {
        try {
            Log::info("Payment confirmation email sent to {$customer->email} for order {$order->order_number}");
            
            // Mail::to($customer->email)->send(new PaymentConfirmation($order));
        } catch (Exception $e) {
            Log::error("Failed to send payment confirmation: " . $e->getMessage());
        }
    }

    /**
     * Send order status update
     */
    public function sendOrderStatusUpdate($customer, $order, string $status): void
    {
        try {
            Log::info("Order status update email sent to {$customer->email}: {$status}");
            
            // Mail::to($customer->email)->send(new OrderStatusUpdate($order, $status));
        } catch (\Exception $e) {
            Log::error("Failed to send order status update: " . $e->getMessage());
        }
    }

    /**
     * Send shipping update
     */
    public function sendShippingUpdate($customer, $order): void
    {
        try {
            Log::info("Shipping update email sent to {$customer->email} for order {$order->order_number}");
            
            // Mail::to($customer->email)->send(new ShippingUpdate($order));
        } catch (Exception $e) {
            Log::error("Failed to send shipping update: " . $e->getMessage());
        }
    }

    /**
     * Send order cancellation notification
     */
    public function sendOrderCancellation($customer, $order, string $reason): void
    {
        try {
            Log::info("Order cancellation email sent to {$customer->email}");
            
            // Mail::to($customer->email)->send(new OrderCancellation($order, $reason));
        } catch (Exception $e) {
            Log::error("Failed to send order cancellation: " . $e->getMessage());
        }
    }

    /**
     * Send refund confirmation
     */
    public function sendRefundConfirmation($customer, $order, float $amount): void
    {
        try {
            Log::info("Refund confirmation email sent to {$customer->email}: ₦{$amount}");
            
            // Mail::to($customer->email)->send(new RefundConfirmation($order, $amount));
        } catch (Exception $e) {
            Log::error("Failed to send refund confirmation: " . $e->getMessage());
        }
    }

    /**
     * Send payment failed notification
     */
    public function sendPaymentFailed($customer, $order, string $reason): void
    {
        try {
            Log::info("Payment failed email sent to {$customer->email}");
            
            // Mail::to($customer->email)->send(new PaymentFailed($order, $reason));
        } catch (Exception $e) {
            Log::error("Failed to send payment failed notification: " . $e->getMessage());
        }
    }

    /**
     * Send welcome email to new customer
     */
    public function sendWelcomeEmail($customer): void
    {
        try {
            Log::info("Welcome email sent to {$customer->email}");
            
            // Mail::to($customer->email)->send(new WelcomeEmail($customer));
        } catch (Exception $e) {
            Log::error("Failed to send welcome email: " . $e->getMessage());
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($customer, string $resetLink): void
    {
        try {
            Log::info("Password reset email sent to {$customer->email}");
            
            // Mail::to($customer->email)->send(new PasswordReset($resetLink));
        } catch (Exception $e) {
            Log::error("Failed to send password reset email: " . $e->getMessage());
        }
    }

    /**
     * Send low stock alert to admin
     */
    public function sendLowStockAlert($product): void
    {
        try {
            $adminEmail = config('mail.admin_email', 'admin@oldenzcollections.com');
            
            Log::info("Low stock alert sent for product: {$product->name}");
            
            // Mail::to($adminEmail)->send(new LowStockAlert($product));
        } catch (Exception $e) {
            Log::error("Failed to send low stock alert: " . $e->getMessage());
        }
    }

    /**
     * Send abandoned cart reminder
     */
    public function sendAbandonedCartReminder($customer, $cart): void
    {
        try {
            Log::info("Abandoned cart reminder sent to {$customer->email}");
            
            // Mail::to($customer->email)->send(new AbandonedCartReminder($cart));
        } catch (Exception $e) {
            Log::error("Failed to send abandoned cart reminder: " . $e->getMessage());
        }
    }

    /**
     * Send back in stock notification
     */
    public function sendBackInStockNotification($customer, $product): void
    {
        try {
            Log::info("Back in stock notification sent to {$customer->email} for {$product->name}");
            
            // Mail::to($customer->email)->send(new BackInStock($product));
        } catch (Exception $e) {
            Log::error("Failed to send back in stock notification: " . $e->getMessage());
        }
    }
}
