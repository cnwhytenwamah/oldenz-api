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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('transaction_reference')->unique();
            $table->string('payment_gateway'); 
            $table->string('payment_method');
            
            $table->enum('status', [
                'pending',
                'processing',
                'successful',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending');
            
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();
            
            $table->string('card_type')->nullable();
            $table->string('card_last_four')->nullable();
            $table->string('bank_name')->nullable();
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            $table->timestamps();

            $table->index('order_id');
            $table->index('transaction_reference');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
