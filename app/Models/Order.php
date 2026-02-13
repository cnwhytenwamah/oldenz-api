<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus as PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'payment_status',
        'fulfillment_status',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'tax_amount',
        'total',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_phone',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'customer_email',
        'customer_note',
        'admin_note',
        'tracking_number',
        'carrier',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatusEnum::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(uniqid());
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get the customer that owns the order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payment for the order
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the shipping address as an array
     */
    public function getShippingAddressAttribute(): array
    {
        return [
            'first_name' => $this->shipping_first_name,
            'last_name' => $this->shipping_last_name,
            'phone' => $this->shipping_phone,
            'address_line_1' => $this->shipping_address_line_1,
            'address_line_2' => $this->shipping_address_line_2,
            'city' => $this->shipping_city,
            'state' => $this->shipping_state,
            'postal_code' => $this->shipping_postal_code,
            'country' => $this->shipping_country,
        ];
    }

    /**
     * Get the billing address as an array
     */
    public function getBillingAddressAttribute(): array
    {
        return [
            'first_name' => $this->billing_first_name,
            'last_name' => $this->billing_last_name,
            'phone' => $this->billing_phone,
            'address_line_1' => $this->billing_address_line_1,
            'address_line_2' => $this->billing_address_line_2,
            'city' => $this->billing_city,
            'state' => $this->billing_state,
            'postal_code' => $this->billing_postal_code,
            'country' => $this->billing_country,
        ];
    }

    /**
     * Mark order as confirmed
     */
    public function markAsConfirmed(): void
    {
        $this->update([
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark order as shipped
     */
    public function markAsShipped(string $trackingNumber = null, string $carrier = null): void
    {
        $this->update([
            'status' => OrderStatus::SHIPPED,
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
        ]);
    }

    /**
     * Mark order as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Cancel the order
     */
    public function cancel(): void
    {
        $this->update([
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED]);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent orders
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
