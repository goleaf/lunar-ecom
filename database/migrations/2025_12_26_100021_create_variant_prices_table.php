<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for variant-specific pricing:
     * - Base price per currency
     * - Compare-at price per currency
     * - Channel-specific pricing
     * - Customer-group pricing
     * - Time-limited pricing (sales)
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('currency_id')
                ->constrained($this->prefix.'currencies')
                ->onDelete('cascade');
            
            // Base price
            $table->integer('price')->nullable()->index();
            
            // Compare-at price (strike price)
            $table->integer('compare_at_price')->nullable();
            
            // Channel-specific pricing
            $table->foreignId('channel_id')
                ->nullable()
                ->constrained($this->prefix.'channels')
                ->nullOnDelete();
            
            // Customer-group pricing (B2B)
            $table->foreignId('customer_group_id')
                ->nullable()
                ->constrained($this->prefix.'customer_groups')
                ->nullOnDelete();
            
            // Tiered pricing (quantity breaks)
            $table->integer('min_quantity')->default(1)->index();
            $table->integer('max_quantity')->nullable()->index();
            
            // Time-limited pricing (sales)
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            
            // Tax configuration
            $table->boolean('tax_inclusive')->default(false);
            
            // Priority (for multiple prices)
            $table->integer('priority')->default(0)->index();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['variant_id', 'currency_id', 'is_active']);
            $table->index(['variant_id', 'channel_id', 'is_active']);
            $table->index(['variant_id', 'customer_group_id', 'is_active']);
            $table->index(['variant_id', 'min_quantity', 'max_quantity']);
            $table->index(['starts_at', 'ends_at', 'is_active']);
            
            // Unique constraint: variant + currency + channel + customer_group + min_quantity
            $table->unique([
                'variant_id',
                'currency_id',
                'channel_id',
                'customer_group_id',
                'min_quantity',
            ], 'variant_price_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_prices');
    }
};

