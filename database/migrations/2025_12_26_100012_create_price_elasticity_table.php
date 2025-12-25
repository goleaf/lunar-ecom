<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for tracking price elasticity data.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'price_elasticity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained($this->prefix.'product_variants')
                ->nullOnDelete();
            
            // Price change
            $table->bigInteger('old_price')->default(0);
            $table->bigInteger('new_price')->default(0);
            $table->decimal('price_change_percent', 8, 4)->default(0);
            $table->timestamp('price_changed_at')->index();
            
            // Sales before/after
            $table->integer('sales_before')->default(0);
            $table->integer('sales_after')->default(0);
            $table->decimal('sales_change_percent', 8, 4)->default(0);
            
            // Revenue before/after
            $table->bigInteger('revenue_before')->default(0);
            $table->bigInteger('revenue_after')->default(0);
            $table->decimal('revenue_change_percent', 8, 4)->default(0);
            
            // Elasticity calculation
            $table->decimal('price_elasticity', 10, 4)->nullable();
            // Formula: % change in quantity / % change in price
            // < -1: Elastic (demand sensitive to price)
            // -1 to 0: Inelastic (demand less sensitive)
            // > 0: Giffen good (demand increases with price)
            
            // Period analyzed
            $table->integer('days_before')->default(30);
            $table->integer('days_after')->default(30);
            $table->date('analysis_date')->index();
            
            // Context
            $table->json('context')->nullable(); // Store additional context (season, promotions, etc.)
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'price_changed_at']);
            $table->index(['variant_id', 'price_changed_at']);
            $table->index(['price_elasticity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_elasticity');
    }
};

