<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for automated product rules (e.g., auto-archive when stock = 0).
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Rule type
            $table->enum('trigger_type', [
                'stock_level',
                'stock_zero',
                'price_change',
                'expiration',
                'date_range',
                'custom',
            ])->index();
            
            // Conditions (JSON)
            $table->json('conditions')->nullable();
            // Example: {"field": "stock", "operator": "equals", "value": 0}
            
            // Actions (JSON)
            $table->json('actions')->nullable();
            // Example: {"type": "archive", "notify": true}
            
            // Scope
            $table->enum('scope', ['all', 'category', 'collection', 'brand', 'tag', 'custom'])
                ->default('all');
            $table->json('scope_filters')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index();
            
            // Execution tracking
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'trigger_type']);
            $table->index(['is_active', 'next_execution_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_automation_rules');
    }
};

