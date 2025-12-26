<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for rule-based (dynamic) collection rules.
     * Allows collections to automatically include products based on conditions.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'collection_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained($this->prefix.'collections')->onDelete('cascade');
            
            // Rule conditions (JSON)
            // Examples:
            // - Category: {"category_id": 1}
            // - Price range: {"price_min": 1000, "price_max": 5000}
            // - Attributes: {"attributes": {"color": "red", "size": "large"}}
            // - Brand: {"brand_id": 1}
            // - Tags: {"tags": ["sale", "featured"]}
            // - Stock status: {"stock_status": "in_stock"}
            // - Date range: {"created_after": "2024-01-01", "created_before": "2024-12-31"}
            $table->json('conditions')->nullable();
            
            // Rule logic: 'and' (all conditions must match) or 'or' (any condition matches)
            $table->enum('logic', ['and', 'or'])->default('and');
            
            // Rule priority (higher = evaluated first)
            $table->integer('priority')->default(0)->index();
            
            // Whether rule is active
            $table->boolean('is_active')->default(true)->index();
            
            // Limit number of products (null = no limit)
            $table->integer('product_limit')->nullable();
            
            // Sort order for products in collection
            $table->string('sort_by')->default('created_at'); // created_at, price, name, popularity, etc.
            $table->enum('sort_direction', ['asc', 'desc'])->default('desc');
            
            $table->timestamps();
            
            $table->index(['collection_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'collection_rules');
    }
};


