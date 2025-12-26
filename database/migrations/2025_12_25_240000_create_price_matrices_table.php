<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = $this->prefix.'price_matrices';

        // This project currently has another earlier migration that creates this table.
        // SQLite (and most DBs) will hard-fail on duplicate CREATE TABLE, so guard it.
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->cascadeOnDelete();
            
            // Matrix configuration
            $table->string('name')->nullable(); // Optional name for the matrix
            $table->enum('matrix_type', ['quantity', 'customer_group', 'region', 'mixed', 'rule_based'])->index();
            $table->text('description')->nullable();
            
            // Priority (higher priority matrices are evaluated first)
            $table->integer('priority')->default(0)->index();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('requires_approval')->default(false)->index(); // For wholesale pricing
            
            // Date range
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            
            // Rules (JSON) - Complex rule definitions
            // {
            //   "conditions": [
            //     {"type": "quantity", "operator": ">=", "value": 10},
            //     {"type": "customer_group", "operator": "=", "value": "wholesale"}
            //   ],
            //   "price": 90.00,
            //   "price_type": "fixed" // or "percentage_discount", "amount_discount"
            // }
            $table->json('rules')->nullable();
            
            // Mix-and-match settings
            $table->boolean('allow_mix_match')->default(false)->index();
            $table->json('mix_match_variants')->nullable(); // Variant IDs that can be mixed
            $table->integer('mix_match_min_quantity')->nullable();
            
            // Minimum order quantities
            $table->integer('min_order_quantity')->nullable();
            $table->integer('max_order_quantity')->nullable();
            
            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_active', 'priority']);
            $table->index(['product_variant_id', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_matrices');
    }
};


