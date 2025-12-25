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
        Schema::create($this->prefix.'bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Bundle configuration
            $table->enum('bundle_type', ['fixed', 'dynamic'])->default('fixed')->index();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage')->index();
            $table->decimal('discount_value', 10, 2)->default(0);
            
            // Dynamic bundle settings
            $table->unsignedInteger('min_items')->nullable(); // Minimum items for dynamic bundles
            $table->unsignedInteger('max_items')->nullable(); // Maximum items for dynamic bundles
            $table->foreignId('category_id')->nullable()->constrained($this->prefix.'categories')->onDelete('set null'); // Category for "Build Your Own"
            
            // Display settings
            $table->boolean('show_individual_prices')->default(true);
            $table->boolean('show_savings')->default(true);
            $table->boolean('allow_individual_returns')->default(false); // Allow returning individual items
            
            // Analytics
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('add_to_cart_count')->default(0);
            $table->unsignedInteger('purchase_count')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'bundles');
    }
};
