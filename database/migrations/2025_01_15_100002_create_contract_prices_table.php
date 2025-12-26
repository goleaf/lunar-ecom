<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained($this->prefix.'price_lists')->onDelete('cascade');
            $table->string('pricing_type')->index(); // 'variant_fixed', 'category', 'margin_based'
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained($this->prefix.'collections')->onDelete('cascade'); // Using collections as categories
            $table->integer('fixed_price')->nullable(); // Fixed price in minor currency units
            $table->decimal('margin_percentage', 5, 2)->nullable(); // Margin-based pricing
            $table->decimal('margin_amount', 10, 2)->nullable(); // Fixed margin amount
            $table->integer('quantity_break')->nullable()->index(); // Quantity break point
            $table->integer('min_quantity')->nullable(); // Minimum order quantity
            $table->integer('price_floor')->nullable(); // Minimum price
            $table->integer('price_ceiling')->nullable(); // Maximum price
            $table->foreignId('currency_id')->nullable()->constrained($this->prefix.'currencies')->onDelete('set null');
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['price_list_id', 'pricing_type']);
            $table->index(['product_variant_id', 'quantity_break']);
            $table->index(['category_id']);
            $table->unique(['price_list_id', 'product_variant_id', 'quantity_break'], 'unique_variant_qty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_prices');
    }
};


