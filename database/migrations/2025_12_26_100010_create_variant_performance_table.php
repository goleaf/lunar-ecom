<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for variant performance analytics.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->foreignId('variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            
            // Date period
            $table->date('date')->index();
            $table->enum('period', ['daily', 'weekly', 'monthly', 'yearly'])
                ->default('daily')
                ->index();
            
            // Views
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
            
            // Sales
            $table->integer('orders')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->decimal('conversion_rate', 5, 4)->default(0);
            
            // Revenue
            $table->bigInteger('revenue')->default(0);
            $table->bigInteger('revenue_discounted')->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            
            // Stock
            $table->integer('stock_turnover')->default(0);
            $table->decimal('stock_turnover_rate', 8, 4)->default(0);
            
            // Price
            $table->bigInteger('average_price')->default(0);
            $table->integer('price_changes')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['variant_id', 'date', 'period']);
            $table->index(['product_id', 'date']);
            $table->index(['variant_id', 'date']);
            $table->index(['conversion_rate']);
            $table->index(['revenue']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_performance');
    }
};

