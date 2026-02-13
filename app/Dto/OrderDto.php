<?php

namespace App\Dto;

use App\Dto\BaseDto;
use App\Dto\AddressDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

readonly class OrderDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public ?string $orderNumber,
        public int $customerId,
        public string $status,
        public string $paymentStatus,
        public string $fulfillmentStatus,
        public float $subtotal,
        public float $discountAmount,
        public float $shippingFee,
        public float $taxAmount,
        public float $total,
        public AddressDto $shippingAddress,
        public AddressDto $billingAddress,
        public string $customerEmail,
        public ?string $customerNote,
        public ?string $adminNote,
        public ?string $trackingNumber,
        public ?string $carrier,
        public ?array $items,
    ) {
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $customerId, string $customerEmail): self
    {
        return new self(
            id: $data['id'] ?? null,
            orderNumber: $data['order_number'] ?? null,
            customerId: $customerId,
            status: $data['status'] ?? OrderStatus::PENDING->value,
            paymentStatus: $data['payment_status'] ?? PaymentStatus::PENDING->value,
            fulfillmentStatus: $data['fulfillment_status'] ?? 'unfulfilled',
            subtotal: (float) $data['subtotal'],
            discountAmount: (float) ($data['discount_amount'] ?? 0),
            shippingFee: (float) ($data['shipping_fee'] ?? 0),
            taxAmount: (float) ($data['tax_amount'] ?? 0),
            total: (float) $data['total'],
            shippingAddress: AddressDto::fromRequest($data['shipping_address'], $customerId),
            billingAddress: isset($data['billing_address']) 
                ? AddressDto::fromRequest($data['billing_address'], $customerId)
                : AddressDto::fromRequest($data['shipping_address'], $customerId),
            customerEmail: $customerEmail,
            customerNote: $data['customer_note'] ?? null,
            adminNote: $data['admin_note'] ?? null,
            trackingNumber: $data['tracking_number'] ?? null,
            carrier: $data['carrier'] ?? null,
            items: $data['items'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'customer_id' => $this->customerId,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'fulfillment_status' => $this->fulfillmentStatus,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'shipping_fee' => $this->shippingFee,
            'tax_amount' => $this->taxAmount,
            'total' => $this->total,
            
            // Shipping address
            'shipping_first_name' => $this->shippingAddress->firstName,
            'shipping_last_name' => $this->shippingAddress->lastName,
            'shipping_phone' => $this->shippingAddress->phone,
            'shipping_address_line_1' => $this->shippingAddress->addressLine1,
            'shipping_address_line_2' => $this->shippingAddress->addressLine2,
            'shipping_city' => $this->shippingAddress->city,
            'shipping_state' => $this->shippingAddress->state,
            'shipping_postal_code' => $this->shippingAddress->postalCode,
            'shipping_country' => $this->shippingAddress->country,
            
            // Billing address
            'billing_first_name' => $this->billingAddress->firstName,
            'billing_last_name' => $this->billingAddress->lastName,
            'billing_phone' => $this->billingAddress->phone,
            'billing_address_line_1' => $this->billingAddress->addressLine1,
            'billing_address_line_2' => $this->billingAddress->addressLine2,
            'billing_city' => $this->billingAddress->city,
            'billing_state' => $this->billingAddress->state,
            'billing_postal_code' => $this->billingAddress->postalCode,
            'billing_country' => $this->billingAddress->country,
            
            'customer_email' => $this->customerEmail,
            'customer_note' => $this->customerNote,
            'admin_note' => $this->adminNote,
            'tracking_number' => $this->trackingNumber,
            'carrier' => $this->carrier,
        ];
    }

    /**
     * Calculate total from components
     */
    public static function calculateTotal(
        float $subtotal,
        float $discountAmount = 0,
        float $shippingFee = 0,
        float $taxAmount = 0
    ): float {
        return $subtotal - $discountAmount + $shippingFee + $taxAmount;
    }
}
