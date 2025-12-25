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
        Schema::create($this->prefix.'bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained($this->prefix.'bundles')->onDelete('cascade');
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->onDelete('cascade');
            
            // Item configuration
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_optional')->default(false)->index(); // For dynamic bundles
            $table->decimal('custom_price_override', 10, 2)->nullable(); // Override individual item price in bundle
            $table->unsignedInteger('display_order')->default(0)->index();
            
            // For dynamic bundles - category/group
            $table->string('group_name')->nullable(); // Group items (e.g., "Choose 2 from this group")
            $table->unsignedInteger('group_min_selection')->nullable(); // Min items from this group
            $table->unsignedInteger('group_max_selection')->nullable(); // Max items from this group
            
            $table->timestamps();
            
            // Prevent duplicate items in same bundle
            $table->unique(['bundle_id', 'product_id', 'product_variant_id'], 'unique_bundle_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'bundle_items');
    }
};
