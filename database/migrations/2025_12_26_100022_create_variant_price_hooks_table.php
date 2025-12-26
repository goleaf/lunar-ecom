<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for dynamic pricing hooks (ERP, AI, rules engine).
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_price_hooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            
            // Hook type
            $table->enum('hook_type', [
                'erp',
                'ai',
                'rules_engine',
                'external_api',
                'custom',
            ])->index();
            
            // Hook identifier
            $table->string('hook_identifier', 100)->index();
            
            // Configuration (JSON)
            $table->json('config')->nullable();
            
            // Priority
            $table->integer('priority')->default(0)->index();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            // Last execution
            $table->timestamp('last_executed_at')->nullable();
            
            // Cache duration (seconds)
            $table->integer('cache_duration')->default(3600);
            
            // Cached price
            $table->integer('cached_price')->nullable();
            $table->timestamp('cached_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['variant_id', 'hook_type', 'is_active']);
            $table->index(['hook_identifier', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_price_hooks');
    }
};


