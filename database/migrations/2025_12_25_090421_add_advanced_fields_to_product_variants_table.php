<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Price override (variant-specific price that overrides base price)
            $table->integer('price_override')->unsigned()->nullable()->after('ean');
            
            // Cost price (what the variant costs to purchase/manufacture)
            $table->integer('cost_price')->unsigned()->nullable()->after('price_override');
            
            // Compare-at price (variant-level, can override prices table)
            $table->integer('compare_at_price')->unsigned()->nullable()->after('cost_price');
            
            // Weight in grams (explicit weight field)
            $table->unsignedInteger('weight')->nullable()->after('compare_at_price');
            
            // Barcode (EAN-13, already exists as 'ean' but adding explicit barcode field)
            // Note: 'ean' field already exists, but we'll add barcode for clarity
            $table->string('barcode', 13)->nullable()->index()->after('weight');
            
            // Enabled/disabled status
            $table->boolean('enabled')->default(true)->index()->after('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'price_override',
                'cost_price',
                'compare_at_price',
                'weight',
                'barcode',
                'enabled',
            ]);
        });
    }
};
