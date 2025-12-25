<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds comprehensive inventory fields to product variants:
     * - Minimum order quantity
     * - Maximum order quantity
     * - Backorder allowed (yes/no/limit)
     * - Backorder limit
     * - Virtual stock flag (for services/digital)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Minimum order quantity
            if (!Schema::hasColumn($this->prefix.'product_variants', 'min_order_quantity')) {
                $table->integer('min_order_quantity')->default(1)->after('backorder');
            }
            
            // Maximum order quantity
            if (!Schema::hasColumn($this->prefix.'product_variants', 'max_order_quantity')) {
                $table->integer('max_order_quantity')->nullable()->after('min_order_quantity');
            }
            
            // Backorder configuration
            if (!Schema::hasColumn($this->prefix.'product_variants', 'backorder_allowed')) {
                $table->enum('backorder_allowed', ['yes', 'no', 'limit'])
                    ->default('no')
                    ->after('max_order_quantity');
            }
            
            // Backorder limit (if backorder_allowed = 'limit')
            if (!Schema::hasColumn($this->prefix.'product_variants', 'backorder_limit')) {
                $table->integer('backorder_limit')->nullable()->after('backorder_allowed');
            }
            
            // Virtual stock flag (for services/digital products)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'is_virtual')) {
                $table->boolean('is_virtual')->default(false)->index()->after('backorder_limit');
            }
            
            // Stock status (calculated field, cached for performance)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'stock_status')) {
                $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock', 'backorder', 'preorder'])
                    ->default('out_of_stock')
                    ->index()
                    ->after('is_virtual');
            }
            
            // Indexes
            $table->index(['stock_status', 'is_virtual']);
            $table->index(['backorder_allowed', 'backorder_limit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $columns = [
                'min_order_quantity',
                'max_order_quantity',
                'backorder_allowed',
                'backorder_limit',
                'is_virtual',
                'stock_status',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'product_variants', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop indexes
            if (Schema::hasColumn($this->prefix.'product_variants', 'stock_status')) {
                $table->dropIndex(['stock_status', 'is_virtual']);
            }
            if (Schema::hasColumn($this->prefix.'product_variants', 'backorder_allowed')) {
                $table->dropIndex(['backorder_allowed', 'backorder_limit']);
            }
        });
    }
};

