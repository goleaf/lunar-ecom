<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds variant pricing fields:
     * - Cost price (internal margin tracking)
     * - Tax-inclusive/exclusive flags
     * - Price rounding rules
     * - MAP pricing (Minimum Advertised Price)
     * - Price lock (cannot be discounted)
     * - Discount override settings
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Cost price (already exists, but ensure it's there)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'cost_price')) {
                $table->integer('cost_price')->nullable()->after('compare_at_price');
            }
            
            // Tax configuration
            if (!Schema::hasColumn($this->prefix.'product_variants', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('cost_price');
            }
            
            // Price rounding rules (JSON)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'price_rounding_rules')) {
                $table->json('price_rounding_rules')->nullable()->after('tax_inclusive');
            }
            
            // MAP pricing (Minimum Advertised Price)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'map_price')) {
                $table->integer('map_price')->nullable()->index()->after('price_rounding_rules');
            }
            
            // Price lock (cannot be discounted)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'price_locked')) {
                $table->boolean('price_locked')->default(false)->index()->after('map_price');
            }
            
            // Discount override (variant-level discount settings)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'discount_override')) {
                $table->json('discount_override')->nullable()->after('price_locked');
            }
            
            // Dynamic pricing hook configuration
            if (!Schema::hasColumn($this->prefix.'product_variants', 'pricing_hook')) {
                $table->string('pricing_hook', 100)->nullable()->index()->after('discount_override');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $columns = [
                'tax_inclusive',
                'price_rounding_rules',
                'map_price',
                'price_locked',
                'discount_override',
                'pricing_hook',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'product_variants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

