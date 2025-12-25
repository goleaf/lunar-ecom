<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds physical properties and shipping fields:
     * - Volumetric weight (calculated)
     * - Shipping class
     * - Fragile / hazardous flags
     * - Country of origin (variant-level override)
     * - HS / customs codes
     * - Lead time (production delay)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Volumetric weight (calculated field, stored for performance)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'volumetric_weight')) {
                $table->integer('volumetric_weight')->nullable()->index()->after('weight');
            }
            
            // Shipping class (e.g., 'standard', 'express', 'oversized', 'fragile')
            if (!Schema::hasColumn($this->prefix.'product_variants', 'shipping_class')) {
                $table->string('shipping_class', 50)->nullable()->index()->after('volumetric_weight');
            }
            
            // Fragile flag
            if (!Schema::hasColumn($this->prefix.'product_variants', 'is_fragile')) {
                $table->boolean('is_fragile')->default(false)->index()->after('shipping_class');
            }
            
            // Hazardous flag
            if (!Schema::hasColumn($this->prefix.'product_variants', 'is_hazardous')) {
                $table->boolean('is_hazardous')->default(false)->index()->after('is_fragile');
            }
            
            // Hazardous class/category
            if (!Schema::hasColumn($this->prefix.'product_variants', 'hazardous_class')) {
                $table->string('hazardous_class', 50)->nullable()->after('is_hazardous');
            }
            
            // Country of origin (variant-level override)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'origin_country')) {
                $table->string('origin_country', 2)->nullable()->index()->after('hazardous_class');
            }
            
            // HS Code (Harmonized System code for customs)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'hs_code')) {
                $table->string('hs_code', 20)->nullable()->index()->after('origin_country');
            }
            
            // Customs description
            if (!Schema::hasColumn($this->prefix.'product_variants', 'customs_description')) {
                $table->text('customs_description')->nullable()->after('hs_code');
            }
            
            // Lead time (production delay in days)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'lead_time_days')) {
                $table->integer('lead_time_days')->default(0)->index()->after('customs_description');
            }
            
            // Volumetric divisor (for volumetric weight calculation, default 5000)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'volumetric_divisor')) {
                $table->integer('volumetric_divisor')->default(5000)->after('lead_time_days');
            }
            
            // Indexes
            $table->index(['is_fragile', 'is_hazardous']);
            $table->index(['shipping_class', 'is_fragile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $columns = [
                'volumetric_weight',
                'shipping_class',
                'is_fragile',
                'is_hazardous',
                'hazardous_class',
                'origin_country',
                'hs_code',
                'customs_description',
                'lead_time_days',
                'volumetric_divisor',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'product_variants', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop indexes
            if (Schema::hasColumn($this->prefix.'product_variants', 'is_fragile')) {
                $table->dropIndex(['is_fragile', 'is_hazardous']);
            }
            if (Schema::hasColumn($this->prefix.'product_variants', 'shipping_class')) {
                $table->dropIndex(['shipping_class', 'is_fragile']);
            }
        });
    }
};

