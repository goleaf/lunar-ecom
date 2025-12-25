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
        Schema::create($this->prefix.'low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_level_id')->constrained($this->prefix.'inventory_levels')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained($this->prefix.'warehouses')->onDelete('cascade');
            
            // Alert details
            $table->integer('current_quantity');
            $table->integer('reorder_point');
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Notification tracking
            $table->boolean('notification_sent')->default(false)->index();
            $table->timestamp('notification_sent_at')->nullable();
            
            $table->timestamps();
            
            // Index for active alerts
            $table->index(['is_resolved', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'low_stock_alerts');
    }
};
