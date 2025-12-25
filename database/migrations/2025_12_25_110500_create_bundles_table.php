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
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Bundle details
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('slug')->unique()->index();
            $table->string('sku')->nullable()->unique()->index();
            
            // Pricing
            $table->enum('pricing_type', ['fixed', 'percentage', 'dynamic'])->default('fixed')->index();
            $table->integer('discount_amount')->nullable(); // Fixed discount in cents or percentage
            $table->integer('bundle_price')->nullable(); // Fixed bundle price in cents
            
            // Inventory
            $table->enum('inventory_type', ['component', 'independent', 'unlimited'])->default('component')->index();
            $table->integer('stock')->default(0)->index();
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            
            // Display
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->integer('display_order')->default(0)->index();
            $table->string('image')->nullable();
            
            // Settings
            $table->boolean('allow_customization')->default(false); // Allow customers to modify bundle items
            $table->boolean('show_individual_prices')->default(true);
            $table->boolean('show_savings')->default(true);
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'display_order']);
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

