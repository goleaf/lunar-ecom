<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'bundle_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained($this->prefix.'bundles')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained($this->prefix.'currencies')->onDelete('cascade');
            $table->foreignId('customer_group_id')->nullable()->constrained($this->prefix.'customer_groups')->onDelete('cascade');
            
            // Pricing
            $table->integer('price')->unsigned(); // Bundle price in cents
            $table->integer('compare_at_price')->nullable()->unsigned(); // Original total price
            $table->integer('min_quantity')->default(1); // Minimum quantity for this price tier
            $table->integer('max_quantity')->nullable(); // Maximum quantity for this price tier
            
            $table->timestamps();
            
            // Unique constraint: one price per bundle per currency per customer group per quantity tier
            $table->unique(['bundle_id', 'currency_id', 'customer_group_id', 'min_quantity'], 'bundle_price_unique');
            
            // Indexes
            $table->index(['bundle_id', 'currency_id']);
            $table->index(['bundle_id', 'min_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'bundle_prices');
    }
};

