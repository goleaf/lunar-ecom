<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates channel ↔ warehouse mapping table for multi-channel fulfillment.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'channel_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained($this->prefix.'channels')
                ->onDelete('cascade');
            $table->foreignId('warehouse_id')
                ->constrained($this->prefix.'warehouses')
                ->onDelete('cascade');
            $table->unsignedInteger('priority')->default(0)->index(); // Lower = higher priority
            $table->boolean('is_default')->default(false)->index(); // Default warehouse for channel
            $table->boolean('is_active')->default(true)->index();
            $table->json('fulfillment_rules')->nullable(); // Channel-specific fulfillment rules
            $table->timestamps();

            // Unique constraint: one mapping per channel-warehouse pair
            $table->unique(['channel_id', 'warehouse_id'], 'channel_warehouse_unique');
            
            // Indexes for performance
            $table->index(['channel_id', 'is_active', 'priority']);
            $table->index(['warehouse_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'channel_warehouse');
    }
};


