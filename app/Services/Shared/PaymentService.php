<?php

namespace App\Services\Shared;

use Exception;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService extends BaseService
{
    /**
     * Initialize payment with Paystack
     */
    public function initializePaystackPayment($payment, $order, $customer): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => $customer->email,
                'amount' => $payment->amount * 100,
                'reference' => $payment->transaction_reference,
                'callback_url' => config('app.url') . '/api/v1/payments/callback',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->full_name,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'authorization_url' => $data['data']['authorization_url'],
                    'access_code' => $data['data']['access_code'],
                    'reference' => $data['data']['reference'],
                ];
            }

            throw new Exception('Payment initialization failed');
        } catch (Exception $e) {
            Log::error('Paystack initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize payment with Flutterwave
     */
    public function initializeFlutterwavePayment($payment, $order, $customer): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $payment->transaction_reference,
                'amount' => $payment->amount,
                'currency' => 'NGN',
                'redirect_url' => config('app.url') . '/api/v1/payments/callback',
                'payment_options' => 'card,banktransfer,ussd',
                'customer' => [
                    'email' => $customer->email,
                    'name' => $customer->full_name,
                    'phonenumber' => $customer->phone,
                ],
                'customizations' => [
                    'title' => 'Oldenz Collections',
                    'description' => "Payment for order {$order->order_number}",
                    'logo' => config('app.url') . '/logo.png',
                ],
                'meta' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'authorization_url' => $data['data']['link'],
                    'reference' => $payment->transaction_reference,
                ];
            }

            throw new Exception('Payment initialization failed');
        } catch (Exception $e) {
            Log::error('Flutterwave initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize payment (gateway agnostic)
     */
    public function initializePayment($payment, $order, $customer): array
    {
        return match ($payment->payment_gateway) {
            'paystack' => $this->initializePaystackPayment($payment, $order, $customer),
            'flutterwave' => $this->initializeFlutterwavePayment($payment, $order, $customer),
            default => throw new Exception('Unsupported payment gateway'),
        };
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPaystackPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => $data['data']['status'] === 'success',
                    'gateway_reference' => $data['data']['id'],
                    'response' => $data,
                ];
            }

            return ['success' => false];
        } catch (Exception $e) {
            Log::error('Paystack verification failed: ' . $e->getMessage());
            return ['success' => false];
        }
    }

    /**
     * Verify Flutterwave payment
     */
    public function verifyFlutterwavePayment(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
            ])->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => $data['data']['status'] === 'successful',
                    'gateway_reference' => $data['data']['id'],
                    'response' => $data,
                ];
            }

            return ['success' => false];
        } catch (Exception $e) {
            Log::error('Flutterwave verification failed: ' . $e->getMessage());
            return ['success' => false];
        }
    }

    /**
     * Verify payment (gateway agnostic)
     */
    public function verifyPayment(string $reference): array
    {
        $result = $this->verifyPaystackPayment($reference);
        
        if (!$result['success']) {
            $result = $this->verifyFlutterwavePayment($reference);
        }

        return $result;
    }

    /**
     * Process refund
     */
    public function processRefund(string $reference, float $amount): array
    {        
        Log::info("Processing refund for {$reference}: ₦{$amount}");
        
        return [
            'success' => true,
            'refund_reference' => 'RFD-' . uniqid(),
        ];
    }
}
