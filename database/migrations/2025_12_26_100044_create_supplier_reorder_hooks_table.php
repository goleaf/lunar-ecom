<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for supplier reorder hooks:
     * - Integration with supplier systems
     * - Automated reorder creation
     * - Reorder tracking
     */
    public function up(): void
    {
        Schema::create($this->prefix.'supplier_reorder_hooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained($this->prefix.'warehouses')
                ->onDelete('set null');
            
            // Supplier information
            $table->string('supplier_name')->nullable();
            $table->string('supplier_code')->nullable();
            $table->string('supplier_sku')->nullable();
            $table->string('supplier_url')->nullable();
            $table->json('supplier_config')->nullable(); // API keys, endpoints, etc.
            
            // Reorder settings
            $table->integer('reorder_point')->default(0); // Trigger reorder at this level
            $table->integer('reorder_quantity')->default(0); // Quantity to reorder
            $table->integer('min_order_quantity')->nullable(); // Minimum order quantity
            $table->integer('max_order_quantity')->nullable(); // Maximum order quantity
            $table->decimal('unit_cost', 10, 2)->nullable(); // Cost per unit
            
            // Automation settings
            $table->enum('trigger_type', [
                'manual',              // Manual reorder only
                'auto_on_low_stock',   // Auto-reorder on low stock
                'auto_on_out_of_stock', // Auto-reorder on out of stock
                'scheduled',           // Scheduled reorder
            ])->default('manual')->index();
            
            $table->json('trigger_conditions')->nullable(); // Custom trigger conditions
            
            // Integration settings
            $table->enum('integration_type', [
                'none',                // No integration
                'api',                 // API integration
                'email',               // Email-based
                'webhook',             // Webhook-based
                'csv_export',          // CSV export
                'erp',                 // ERP integration
            ])->default('none')->index();
            
            $table->json('integration_config')->nullable(); // Integration-specific config
            
            // Status tracking
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_reorder_at')->nullable();
            $table->integer('reorder_count')->default(0);
            $table->text('last_reorder_response')->nullable(); // Response from supplier
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_variant_id', 'is_active']);
            $table->index(['trigger_type', 'is_active']);
            $table->index(['integration_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'supplier_reorder_hooks');
    }
};


