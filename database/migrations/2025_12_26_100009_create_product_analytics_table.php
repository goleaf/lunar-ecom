<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for aggregated product analytics.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            
            // Date period
            $table->date('date')->index();
            $table->enum('period', ['daily', 'weekly', 'monthly', 'yearly'])
                ->default('daily')
                ->index();
            
            // Views
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
            
            // Conversions
            $table->integer('orders')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->decimal('conversion_rate', 5, 4)->default(0)->index();
            
            // Revenue
            $table->bigInteger('revenue')->default(0); // In smallest currency unit
            $table->bigInteger('revenue_discounted')->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            
            // Cart metrics
            $table->integer('cart_additions')->default(0);
            $table->integer('cart_removals')->default(0);
            $table->integer('abandoned_carts')->default(0);
            $table->decimal('abandoned_cart_rate', 5, 4)->default(0);
            
            // Stock metrics
            $table->integer('stock_turnover')->default(0);
            $table->decimal('stock_turnover_rate', 8, 4)->default(0);
            $table->integer('stock_level_start')->default(0);
            $table->integer('stock_level_end')->default(0);
            
            // Price metrics
            $table->bigInteger('average_price')->default(0);
            $table->bigInteger('min_price')->default(0);
            $table->bigInteger('max_price')->default(0);
            $table->integer('price_changes')->default(0);
            
            // Engagement
            $table->integer('wishlist_additions')->default(0);
            $table->integer('reviews_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['product_id', 'date', 'period']);
            $table->index(['date', 'period']);
            $table->index(['product_id', 'date']);
            // Note: conversion_rate already has an index from line 35
            $table->index(['revenue']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_analytics');
    }
};

