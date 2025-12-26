<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for inventory automation rules:
     * - Auto-disable variants on out of stock
     * - Auto-enable variants on restock
     * - Custom automation triggers
     */
    public function up(): void
    {
        Schema::create($this->prefix.'inventory_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->string('name'); // Rule name
            $table->text('description')->nullable();
            
            // Trigger conditions
            $table->enum('trigger_type', [
                'low_stock',           // Trigger when stock <= threshold
                'out_of_stock',        // Trigger when stock = 0
                'restock',             // Trigger when stock increases
                'below_safety_stock',  // Trigger when below safety stock
                'custom',              // Custom trigger logic
            ])->index();
            
            $table->json('trigger_conditions')->nullable(); // Custom conditions
            
            // Actions
            $table->enum('action_type', [
                'disable_variant',     // Disable variant
                'enable_variant',      // Enable variant
                'hide_variant',        // Hide variant
                'show_variant',        // Show variant
                'send_alert',          // Send alert notification
                'create_reorder',      // Create supplier reorder
                'custom',              // Custom action
            ])->index();
            
            $table->json('action_config')->nullable(); // Action-specific configuration
            
            // Rule settings
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0)->index();
            $table->boolean('run_once')->default(false); // Run only once per trigger
            $table->integer('cooldown_minutes')->nullable(); // Cooldown between triggers
            
            // Execution tracking
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'trigger_type']);
            $table->index(['product_variant_id', 'is_active']);
            $table->index(['product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'inventory_automation_rules');
    }
};


