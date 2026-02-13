<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->default('pending');

            $table->enum('payment_status', [
                'pending',
                'processing',
                'successful',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending');

            $table->enum('fulfillment_status', [
                'unfulfilled',
                'partially_fulfilled',
                'fulfilled'
            ])->default('unfulfilled');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->string('shipping_first_name');
            $table->string('shipping_last_name');
            $table->string('shipping_phone');
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country')->default('Nigeria');

            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_phone');
            $table->string('billing_address_line_1');
            $table->string('billing_address_line_2')->nullable();
            $table->string('billing_city');
            $table->string('billing_state');
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country')->default('Nigeria');

            $table->string('customer_email');
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('order_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('fulfillment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
