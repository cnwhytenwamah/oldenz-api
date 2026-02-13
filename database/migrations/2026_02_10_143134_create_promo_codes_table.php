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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable(); 
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            
            $table->integer('usage_limit')->nullable(); 
            $table->integer('usage_limit_per_customer')->nullable();
            $table->integer('usage_count')->default(0);
            
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->json('applicable_categories')->nullable(); 
            $table->json('applicable_products')->nullable(); 
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
