<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for channel-specific product data:
     * - Visibility per channel
     * - Descriptions per channel
     * - SEO fields per channel
     */
    public function up(): void
    {
        Schema::create($this->prefix.'channel_product_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->foreignId('channel_id')
                ->constrained($this->prefix.'channels')
                ->onDelete('cascade');
            
            // Channel-specific visibility
            $table->enum('visibility', ['public', 'private', 'scheduled'])
                ->default('public')
                ->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_publish_at')->nullable();
            $table->timestamp('scheduled_unpublish_at')->nullable();
            
            // Channel-specific descriptions
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->longText('technical_description')->nullable();
            
            // Channel-specific SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Geo-restrictions
            $table->json('allowed_countries')->nullable(); // ISO country codes
            $table->json('blocked_countries')->nullable(); // ISO country codes
            $table->json('allowed_regions')->nullable(); // Custom regions
            
            $table->timestamps();
            
            // Unique constraint: one record per product-channel combination
            $table->unique(['product_id', 'channel_id']);
            
            // Indexes for efficient queries
            $table->index(['channel_id', 'is_visible', 'published_at']);
            $table->index(['product_id', 'channel_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'channel_product_data');
    }
};

