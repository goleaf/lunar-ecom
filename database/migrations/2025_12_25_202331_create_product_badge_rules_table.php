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
        Schema::create($this->prefix.'product_badge_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained($this->prefix.'product_badges')->cascadeOnDelete();
            
            // Rule configuration
            $table->enum('condition_type', ['manual', 'automatic'])->default('automatic')->index();
            $table->string('name')->nullable(); // Rule name for identification
            $table->text('description')->nullable();
            
            // Conditions (JSON)
            $table->json('conditions')->nullable(); // {
            //   is_new: { enabled: true, days: 30 },
            //   on_sale: { enabled: true },
            //   low_stock: { enabled: true, threshold: 10 },
            //   best_seller: { enabled: true, sales_threshold: 100 },
            //   featured: { enabled: true },
            //   price_range: { enabled: true, min: 0, max: 1000 },
            //   category: { enabled: true, category_ids: [1, 2, 3] },
            //   tag: { enabled: true, tag_ids: [1, 2, 3] },
            //   custom_field: { enabled: true, field: 'exclusive', value: true }
            // }
            
            // Priority for rule evaluation (higher priority rules evaluated first)
            $table->integer('priority')->default(0)->index();
            
            // Rule status
            $table->boolean('is_active')->default(true)->index();
            
            // Expiration
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['badge_id', 'is_active', 'condition_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_badge_rules');
    }
};
