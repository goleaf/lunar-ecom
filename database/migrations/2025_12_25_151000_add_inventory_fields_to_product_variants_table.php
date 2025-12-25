<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds inventory-related fields to product variants:
     * - out_of_stock_visibility: Control visibility when out of stock
     * - preorder_enabled: Enable pre-order support
     * - preorder_release_date: Expected release date for pre-orders
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Out-of-stock visibility rules
            // Options: 'hide' (hide completely), 'show_unavailable' (show but mark unavailable), 'show_available' (show as available)
            $table->enum('out_of_stock_visibility', ['hide', 'show_unavailable', 'show_available'])
                ->default('show_unavailable')
                ->after('purchasable');
            
            // Pre-order support
            $table->boolean('preorder_enabled')->default(false)->after('out_of_stock_visibility');
            $table->dateTime('preorder_release_date')->nullable()->after('preorder_enabled');
            
            // Index for filtering
            $table->index(['out_of_stock_visibility', 'preorder_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->dropIndex(['out_of_stock_visibility', 'preorder_enabled']);
            $table->dropColumn([
                'out_of_stock_visibility',
                'preorder_enabled',
                'preorder_release_date',
            ]);
        });
    }
};

