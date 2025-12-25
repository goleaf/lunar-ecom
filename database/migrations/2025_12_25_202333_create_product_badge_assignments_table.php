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
        Schema::create($this->prefix.'product_badge_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained($this->prefix.'product_badges')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            
            // Assignment type
            $table->enum('assignment_type', ['manual', 'automatic'])->default('automatic')->index();
            $table->foreignId('rule_id')->nullable()->constrained($this->prefix.'product_badge_rules')->nullOnDelete();
            
            // Display settings (can override badge defaults)
            $table->integer('priority')->nullable(); // Override badge priority for this product
            $table->string('display_position')->nullable(); // Override badge position for this product
            
            // Visibility rules
            $table->json('visibility_rules')->nullable(); // {
            //   show_on_category: true,
            //   show_on_product: true,
            //   show_on_search: true,
            //   show_everywhere: true
            // }
            
            // Expiration
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Tracking
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Unique constraint: one badge per product (unless multiple badges allowed)
            $table->unique(['badge_id', 'product_id'], 'badge_product_unique');
            
            // Indexes
            $table->index(['product_id', 'assignment_type']);
            $table->index(['badge_id', 'is_active']);
            $table->index(['expires_at']);
        });
        
        // Add is_active column after creation (for soft deletes or active/inactive)
        Schema::table($this->prefix.'product_badge_assignments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_badge_assignments');
    }
};
