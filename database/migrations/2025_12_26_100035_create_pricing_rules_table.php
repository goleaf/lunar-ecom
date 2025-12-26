<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for pricing rules:
     * - Rule types: fixed_price, percentage_discount, absolute_discount, cost_plus, margin_protected, map_enforcement, rounding, currency_override
     * - Priority, scope, conditions, validity window
     * - Stackable/non-stackable flag
     */
    public function up(): void
    {
        Schema::create($this->prefix.'pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique()->index();
            $table->text('description')->nullable();
            
            // Rule type
            $table->enum('rule_type', [
                'fixed_price',
                'percentage_discount',
                'absolute_discount',
                'cost_plus',
                'margin_protected',
                'map_enforcement',
                'rounding',
                'currency_override',
            ])->index();
            
            // Priority (higher = applied first)
            $table->integer('priority')->default(0)->index();
            
            // Scope (what this rule applies to)
            $table->enum('scope_type', [
                'global',
                'product',
                'variant',
                'category',
                'collection',
                'brand',
                'customer_group',
                'channel',
                'customer',
            ])->default('global')->index();
            
            $table->foreignId('product_id')->nullable()->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained($this->prefix.'collections')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained($this->prefix.'brands')->onDelete('cascade');
            $table->foreignId('customer_group_id')->nullable()->constrained($this->prefix.'customer_groups')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained($this->prefix.'channels')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Conditions (JSON)
            $table->json('conditions')->nullable(); // e.g., min_quantity, min_order_value, product_tags, etc.
            
            // Rule configuration (JSON)
            $table->json('rule_config')->nullable(); // Rule-specific configuration
            
            // Validity window
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            
            // Stackable flag
            $table->boolean('is_stackable')->default(false)->index();
            $table->integer('max_stack_depth')->nullable()->after('is_stackable'); // Max number of stackable rules
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            // Currency-specific overrides
            $table->foreignId('currency_id')->nullable()->constrained($this->prefix.'currencies')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['rule_type', 'is_active', 'priority']);
            $table->index(['scope_type', 'is_active']);
            $table->index(['starts_at', 'ends_at', 'is_active']);
            $table->index(['is_stackable', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'pricing_rules');
    }
};


