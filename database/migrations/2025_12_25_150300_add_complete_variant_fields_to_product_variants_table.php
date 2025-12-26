<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds complete variant fields as per requirements:
     * - Variant name (explicit name field)
     * - Low-stock threshold
     * - Variant ordering/priority (position)
     * - Variant-specific SEO (meta_title, meta_description, meta_keywords)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Variant name (explicit name field, e.g., "Red / XL")
            // Falls back to generated name from option values if not set
            $table->string('variant_name')->nullable()->after('enabled');
            
            // Low-stock threshold (variant-level threshold for stock alerts)
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('variant_name');
            
            // Variant ordering/priority (for sorting variants within a product)
            $table->unsignedInteger('position')->default(0)->index()->after('low_stock_threshold');
            
            // Variant-specific SEO fields
            $table->string('meta_title')->nullable()->after('position');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            
            // Index for variant ordering queries
            $table->index(['product_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'position']);
            $table->dropColumn([
                'variant_name',
                'low_stock_threshold',
                'position',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ]);
        });
    }
};


