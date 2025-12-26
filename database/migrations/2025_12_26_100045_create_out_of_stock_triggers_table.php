<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for out-of-stock triggers:
     * - Track when variants go out of stock
     * - Trigger automation rules
     * - Track recovery (restock)
     */
    public function up(): void
    {
        Schema::create($this->prefix.'out_of_stock_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained($this->prefix.'warehouses')
                ->onDelete('set null');
            
            // Trigger details
            $table->timestamp('triggered_at')->index();
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->string('trigger_reason')->nullable(); // sale, adjustment, transfer, etc.
            
            // Recovery tracking
            $table->boolean('is_recovered')->default(false)->index();
            $table->timestamp('recovered_at')->nullable();
            $table->integer('recovery_quantity')->nullable();
            $table->string('recovery_reason')->nullable();
            
            // Automation tracking
            $table->boolean('automation_triggered')->default(false);
            $table->json('automation_actions')->nullable(); // Actions taken
            
            // Notification tracking
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_variant_id', 'is_recovered']);
            $table->index(['triggered_at', 'is_recovered']);
            $table->index(['warehouse_id', 'is_recovered']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'out_of_stock_triggers');
    }
};


