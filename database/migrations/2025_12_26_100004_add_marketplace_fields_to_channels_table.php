<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds marketplace-ready fields to channels table:
     * - Marketplace type (amazon, ebay, shopify, custom, etc.)
     * - Marketplace configuration (API keys, settings)
     * - Currency settings per channel
     * - Language settings per channel
     * - Geo-restrictions per channel
     */
    public function up(): void
    {
        Schema::table($this->prefix.'channels', function (Blueprint $table) {
            // Marketplace type
            $table->string('marketplace_type')->nullable()->after('url')->index();
            // Options: 'webstore', 'amazon', 'ebay', 'etsy', 'shopify', 'woocommerce', 'custom'
            
            // Marketplace configuration (JSON)
            $table->json('marketplace_config')->nullable()->after('marketplace_type');
            // Stores: API keys, marketplace-specific settings, sync settings, etc.
            
            // Default currency for this channel
            $table->foreignId('default_currency_id')->nullable()->after('marketplace_config')
                ->constrained($this->prefix.'currencies')->nullOnDelete();
            
            // Default language for this channel
            $table->foreignId('default_language_id')->nullable()->after('default_currency_id')
                ->constrained($this->prefix.'languages')->nullOnDelete();
            
            // Geo-restrictions at channel level
            $table->json('allowed_countries')->nullable()->after('default_language_id');
            $table->json('blocked_countries')->nullable()->after('allowed_countries');
            $table->json('allowed_regions')->nullable()->after('blocked_countries');
            
            // Channel settings
            $table->boolean('is_active')->default(true)->index()->after('allowed_regions');
            $table->boolean('sync_enabled')->default(false)->index()->after('is_active');
            $table->timestamp('last_synced_at')->nullable()->after('sync_enabled');
            
            // Indexes
            $table->index(['marketplace_type', 'is_active']);
            $table->index(['is_active', 'sync_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'channels', function (Blueprint $table) {
            $table->dropIndex(['marketplace_type', 'is_active']);
            $table->dropIndex(['is_active', 'sync_enabled']);
            $table->dropForeign(['default_currency_id']);
            $table->dropForeign(['default_language_id']);
            $table->dropColumn([
                'marketplace_type',
                'marketplace_config',
                'default_currency_id',
                'default_language_id',
                'allowed_countries',
                'blocked_countries',
                'allowed_regions',
                'is_active',
                'sync_enabled',
                'last_synced_at',
            ]);
        });
    }
};

