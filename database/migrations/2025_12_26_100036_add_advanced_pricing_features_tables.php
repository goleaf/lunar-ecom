<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for advanced pricing features:
     * - Scheduled price changes
     * - Price simulation
     * - Margin alerts
     * - Historical price tracking
     */
    public function up(): void
    {
        // Add scheduled price change fields to variant_prices
        $variantPricesTable = $this->prefix.'variant_prices';
        if (Schema::hasTable($variantPricesTable)) {
            Schema::table($variantPricesTable, function (Blueprint $table) use ($variantPricesTable) {
                if (! Schema::hasColumn($variantPricesTable, 'scheduled_change_at')) {
                    $table->timestamp('scheduled_change_at')->nullable()->after('ends_at')->index();
                }

                if (! Schema::hasColumn($variantPricesTable, 'scheduled_price')) {
                    $table->integer('scheduled_price')->nullable()->after('scheduled_change_at');
                }

                if (! Schema::hasColumn($variantPricesTable, 'is_flash_deal')) {
                    $table->boolean('is_flash_deal')->default(false)->after('scheduled_price')->index();
                }
            });
        }

        // Price simulation table
        $priceSimulationsTable = $this->prefix.'price_simulations';
        if (! Schema::hasTable($priceSimulationsTable)) {
            Schema::create($priceSimulationsTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('currency_id')
                ->constrained($this->prefix.'currencies')
                ->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->foreignId('channel_id')->nullable()->constrained($this->prefix.'channels')->nullOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained($this->prefix.'customer_groups')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('base_price');
            $table->integer('final_price');
            $table->json('applied_rules')->nullable(); // Rules that would apply
            $table->json('pricing_breakdown')->nullable(); // Detailed breakdown
            $table->text('simulation_context')->nullable(); // Context used for simulation
            $table->timestamps();

            $table->index(['product_variant_id', 'currency_id', 'quantity']);
            });
        }

        // Margin alerts table
        $marginAlertsTable = $this->prefix.'margin_alerts';
        if (! Schema::hasTable($marginAlertsTable)) {
            Schema::create($marginAlertsTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->enum('alert_type', ['low_margin', 'negative_margin', 'margin_threshold'])->index();
            $table->decimal('current_margin_percentage', 5, 2);
            $table->decimal('threshold_margin_percentage', 5, 2)->nullable();
            $table->integer('current_price');
            $table->integer('cost_price');
            $table->text('message')->nullable();
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'is_resolved', 'created_at']);
            });
        }

        // Historical price tracking table
        $priceHistoryTable = $this->prefix.'price_history';
        if (! Schema::hasTable($priceHistoryTable)) {
            Schema::create($priceHistoryTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('currency_id')
                ->constrained($this->prefix.'currencies')
                ->onDelete('cascade');
            $table->integer('price');
            $table->integer('compare_at_price')->nullable();
            $table->foreignId('channel_id')->nullable()->constrained($this->prefix.'channels')->nullOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained($this->prefix.'customer_groups')->nullOnDelete();
            $table->string('pricing_layer')->nullable(); // Which layer set this price
            $table->foreignId('pricing_rule_id')->nullable()->constrained($this->prefix.'pricing_rules')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason')->nullable(); // Manual, scheduled, rule, etc.
            $table->json('change_metadata')->nullable(); // Additional change information
            $table->timestamp('effective_from')->index();
            $table->timestamp('effective_to')->nullable()->index();
            $table->timestamps();

            $table->index(['product_variant_id', 'currency_id', 'effective_from']);
            $table->index(['product_variant_id', 'effective_from', 'effective_to']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_history');
        Schema::dropIfExists($this->prefix.'margin_alerts');
        Schema::dropIfExists($this->prefix.'price_simulations');

        Schema::table($this->prefix.'variant_prices', function (Blueprint $table) {
            $columns = ['scheduled_change_at', 'scheduled_price', 'is_flash_deal'];
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'variant_prices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


