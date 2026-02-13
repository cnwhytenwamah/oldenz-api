<?php

namespace App\Dto;

use App\Enums\PaymentStatus;

readonly class PaymentDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public int $orderId,
        public string $transactionReference,
        public string $paymentGateway,
        public string $paymentMethod,
        public string $status,
        public float $amount,
        public string $currency,
        public ?string $gatewayReference,
        public ?array $gatewayResponse,
        public ?string $cardType,
        public ?string $cardLastFour,
        public ?string $bankName,
    ) {  }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $orderId): self
    {
        return new self(
            id: $data['id'] ?? null,
            orderId: $orderId,
            transactionReference: $data['transaction_reference'] ?? self::generateReference(),
            paymentGateway: $data['payment_gateway'],
            paymentMethod: $data['payment_method'],
            status: $data['status'] ?? PaymentStatus::PENDING->value,
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'NGN',
            gatewayReference: $data['gateway_reference'] ?? null,
            gatewayResponse: $data['gateway_response'] ?? null,
            cardType: $data['card_type'] ?? null,
            cardLastFour: $data['card_last_four'] ?? null,
            bankName: $data['bank_name'] ?? null,
        );
    }

    /**
     * Create from gateway response (Paystack)
     */
    public static function fromPaystackResponse(array $response, int $orderId, float $amount): self
    {
        $authorization = $response['data']['authorization'] ?? [];
        
        return new self(
            id: null,
            orderId: $orderId,
            transactionReference: $response['data']['reference'],
            paymentGateway: 'paystack',
            paymentMethod: $authorization['channel'] ?? 'card',
            status: $response['data']['status'] === 'success' 
                ? PaymentStatus::SUCCESSFUL->value 
                : PaymentStatus::FAILED->value,
            amount: $amount,
            currency: $response['data']['currency'] ?? 'NGN',
            gatewayReference: $response['data']['id'] ?? null,
            gatewayResponse: $response,
            cardType: $authorization['card_type'] ?? null,
            cardLastFour: $authorization['last4'] ?? null,
            bankName: $authorization['bank'] ?? null,
        );
    }

    /**
     * Create from gateway response (Flutterwave)
     */
    public static function fromFlutterwaveResponse(array $response, int $orderId, float $amount): self
    {
        $card = $response['data']['card'] ?? [];
        
        return new self(
            id: null,
            orderId: $orderId,
            transactionReference: $response['data']['tx_ref'],
            paymentGateway: 'flutterwave',
            paymentMethod: $response['data']['payment_type'] ?? 'card',
            status: $response['data']['status'] === 'successful' 
                ? PaymentStatus::SUCCESSFUL->value 
                : PaymentStatus::FAILED->value,
            amount: $amount,
            currency: $response['data']['currency'] ?? 'NGN',
            gatewayReference: $response['data']['id'] ?? null,
            gatewayResponse: $response,
            cardType: $card['type'] ?? null,
            cardLastFour: $card['last_4digits'] ?? null,
            bankName: null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'transaction_reference' => $this->transactionReference,
            'payment_gateway' => $this->paymentGateway,
            'payment_method' => $this->paymentMethod,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway_reference' => $this->gatewayReference,
            'gateway_response' => $this->gatewayResponse,
            'card_type' => $this->cardType,
            'card_last_four' => $this->cardLastFour,
            'bank_name' => $this->bankName,
        ];
    }

    /**
     * Generate unique transaction reference
     */
    public static function generateReference(): string
    {
        return 'TXN-' . strtoupper(uniqid()) . '-' . time();
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCESSFUL->value;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING->value;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED->value;
    }
}
