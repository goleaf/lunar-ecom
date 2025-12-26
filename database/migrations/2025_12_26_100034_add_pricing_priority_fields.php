<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds priority and pricing layer fields to variant_prices table:
     * - pricing_layer: manual_override, contract, customer_group, channel, promotional, tiered, base
     * - priority: Higher priority = checked first
     * - customer_id: For contract/customer-specific pricing
     */
    public function up(): void
    {
        Schema::table($this->prefix.'variant_prices', function (Blueprint $table) {
            // Pricing layer type
            $table->enum('pricing_layer', [
                'manual_override',
                'contract',
                'customer_group',
                'channel',
                'promotional',
                'tiered',
                'base',
            ])->default('base')->after('priority')->index();
            
            // Customer-specific pricing (for contracts)
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade')
                ->after('customer_group_id');
            
            // Contract reference
            $table->foreignId('contract_id')
                ->nullable()
                ->constrained($this->prefix.'b2b_contracts')
                ->onDelete('cascade')
                ->after('customer_id');
            
            // Manual override flag
            $table->boolean('is_manual_override')->default(false)->after('pricing_layer')->index();
            
            // Update priority index to include pricing_layer
            $table->index(['pricing_layer', 'priority'], 'pricing_layer_priority_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'variant_prices', function (Blueprint $table) {
            $table->dropIndex('pricing_layer_priority_index');
            $table->dropColumn([
                'pricing_layer',
                'customer_id',
                'contract_id',
                'is_manual_override',
            ]);
        });
    }
};


