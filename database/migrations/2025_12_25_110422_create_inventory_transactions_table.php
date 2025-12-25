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
        Schema::create($this->prefix.'inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained($this->prefix.'warehouses')->onDelete('cascade');
            
            // Transaction details
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return', 'transfer_in', 'transfer_out', 'reservation', 'release'])->index();
            $table->integer('quantity'); // Positive for additions, negative for subtractions
            $table->integer('quantity_before')->nullable(); // Quantity before transaction
            $table->integer('quantity_after')->nullable(); // Quantity after transaction
            
            // Reference information
            $table->string('reference_type')->nullable(); // Order, PurchaseOrder, Adjustment, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the reference
            $table->string('reference_number')->nullable()->index(); // Human-readable reference (order number, etc.)
            
            // Additional information
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for reporting
            $table->index(['type', 'created_at']);
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['product_variant_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'inventory_transactions');
    }
};
