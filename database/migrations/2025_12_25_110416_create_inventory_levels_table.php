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
        Schema::create($this->prefix.'inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained($this->prefix.'warehouses')->onDelete('cascade');
            
            // Stock quantities
            $table->integer('quantity')->default(0)->index(); // Available quantity
            $table->integer('reserved_quantity')->default(0)->index(); // Reserved for orders
            $table->integer('incoming_quantity')->default(0)->index(); // Expected incoming stock
            
            // Reorder settings
            $table->integer('reorder_point')->default(0)->index(); // Alert when quantity < this
            $table->integer('reorder_quantity')->default(0); // Suggested order quantity
            
            // Inventory status
            $table->enum('status', ['in_stock', 'low_stock', 'out_of_stock', 'backorder', 'preorder'])->default('in_stock')->index();
            
            $table->timestamps();
            
            // Unique constraint: one inventory level per variant per warehouse
            $table->unique(['product_variant_id', 'warehouse_id']);
            
            // Indexes for performance
            $table->index(['warehouse_id', 'status']);
            $table->index(['product_variant_id', 'quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'inventory_levels');
    }
};
