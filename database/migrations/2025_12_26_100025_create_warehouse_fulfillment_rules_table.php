<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for warehouse fulfillment priority rules.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'warehouse_fulfillment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')
                ->constrained($this->prefix.'warehouses')
                ->onDelete('cascade');
            
            // Rule type
            $table->enum('rule_type', [
                'geo_location',      // Based on customer location
                'product_type',      // Based on product type
                'order_value',       // Based on order value
                'order_weight',      // Based on order weight
                'customer_group',    // Based on customer group
                'channel',           // Based on sales channel
                'custom',            // Custom rule
            ])->index();
            
            // Rule configuration (JSON)
            $table->json('rule_config')->nullable();
            
            // Priority (lower = higher priority)
            $table->integer('priority')->default(0)->index();
            
            // Conditions (JSON)
            $table->json('conditions')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['warehouse_id', 'rule_type', 'is_active']);
            $table->index(['warehouse_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'warehouse_fulfillment_rules');
    }
};


