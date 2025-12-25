<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_badge_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained($this->prefix.'product_badges')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            
            // Metrics
            $table->integer('views')->default(0); // Product page views with badge
            $table->integer('clicks')->default(0); // Clicks on products with badge
            $table->integer('add_to_cart')->default(0); // Add to cart actions
            $table->integer('purchases')->default(0); // Completed purchases
            $table->decimal('revenue', 15, 2)->default(0); // Revenue from products with this badge
            
            // Conversion rates (calculated)
            $table->decimal('click_through_rate', 5, 2)->default(0); // clicks / views * 100
            $table->decimal('conversion_rate', 5, 2)->default(0); // purchases / views * 100
            $table->decimal('add_to_cart_rate', 5, 2)->default(0); // add_to_cart / views * 100
            
            // Time period
            $table->date('period_start');
            $table->date('period_end');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['badge_id', 'product_id']);
            $table->index(['period_start', 'period_end']);
            $table->unique(['badge_id', 'product_id', 'period_start', 'period_end'], 'badge_product_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_badge_performance');
    }
};
