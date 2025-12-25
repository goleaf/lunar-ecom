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
            
            // Item details
            $table->integer('quantity')->default(1);
            $table->integer('min_quantity')->default(1); // Minimum required quantity
            $table->integer('max_quantity')->nullable(); // Maximum allowed quantity
            $table->boolean('is_required')->default(true)->index(); // Required or optional item
            $table->boolean('is_default')->default(false)->index(); // Default selected item
            
            // Pricing override
            $table->integer('price_override')->nullable(); // Override product price for this bundle
            $table->integer('discount_amount')->nullable(); // Item-specific discount
            
            // Display
            $table->integer('display_order')->default(0)->index();
            $table->text('notes')->nullable(); // Additional notes for this item
            
            $table->timestamps();
            
            // Indexes
            $table->index(['bundle_id', 'display_order']);
            $table->index(['bundle_id', 'is_required']);
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

