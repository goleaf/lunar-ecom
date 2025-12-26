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
        Schema::create($this->prefix.'stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained($this->prefix.'warehouses')->onDelete('set null');
            $table->foreignId('inventory_level_id')->nullable()->constrained($this->prefix.'inventory_levels')->onDelete('set null');
            
            // Movement details
            // Types: sale, return, manual_adjustment, import, damage, transfer, correction, reservation, release, loss
            $table->enum('type', [
                'sale',              // Sale/order fulfillment
                'return',             // Customer return
                'manual_adjustment',  // Manual adjustment
                'import',             // Import/bulk import
                'damage',             // Damaged stock
                'transfer',           // Warehouse transfer
                'correction',         // Correction/adjustment
                'reservation',        // Stock reservation
                'release',            // Reservation release
                'loss',               // Stock loss/theft
                'in',                 // Generic incoming
                'out',                // Generic outgoing
            ])->index();
            $table->integer('quantity')->index(); // Positive for in, negative for out
            $table->integer('quantity_before')->default(0); // Stock level before movement
            $table->integer('quantity_after')->default(0); // Stock level after movement
            
            // Reference information
            $table->string('reference_type')->nullable()->index(); // Order, Adjustment, Transfer, etc.
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('reference_number')->nullable(); // Order number, adjustment ID, etc.
            
            // Additional details
            $table->text('reason')->nullable(); // Reason for movement
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamp('movement_date')->index();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['product_variant_id', 'movement_date']);
            $table->index(['warehouse_id', 'movement_date']);
            $table->index(['type', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'stock_movements');
    }
};

