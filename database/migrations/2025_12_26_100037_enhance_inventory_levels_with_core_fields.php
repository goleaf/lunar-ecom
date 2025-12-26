<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds core inventory fields to inventory_levels table:
     * - On-hand quantity (already exists as 'quantity')
     * - Reserved quantity (already exists)
     * - Available quantity (computed)
     * - Incoming quantity (already exists)
     * - Damaged quantity (new)
     * - Preorder quantity (new)
     * - Safety stock level (new, separate from reorder_point)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'inventory_levels', function (Blueprint $table) {
            // Add damaged quantity field
            if (!Schema::hasColumn($this->prefix.'inventory_levels', 'damaged_quantity')) {
                $table->integer('damaged_quantity')->default(0)->after('incoming_quantity')->index();
            }
            
            // Add preorder quantity field
            if (!Schema::hasColumn($this->prefix.'inventory_levels', 'preorder_quantity')) {
                $table->integer('preorder_quantity')->default(0)->after('damaged_quantity')->index();
            }
            
            // Add safety stock level (separate from reorder_point)
            if (!Schema::hasColumn($this->prefix.'inventory_levels', 'safety_stock_level')) {
                $table->integer('safety_stock_level')->default(0)->after('reorder_point')->index();
            }
            
            // Add backorder limit (per warehouse, can override variant-level)
            if (!Schema::hasColumn($this->prefix.'inventory_levels', 'backorder_limit')) {
                $table->integer('backorder_limit')->nullable()->after('preorder_quantity')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'inventory_levels', function (Blueprint $table) {
            $columns = ['damaged_quantity', 'preorder_quantity', 'safety_stock_level', 'backorder_limit'];
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'inventory_levels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


