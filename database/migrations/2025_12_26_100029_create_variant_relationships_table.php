<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for variant relationships:
     * - Cross-variant linking (same product, different attributes)
     * - Replacement variants
     * - Upgrade / downgrade variants
     * - Accessory variants
     * - Bundle component variants
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('related_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->enum('relationship_type', [
                'cross_variant',      // Same product, different attributes (e.g., different color)
                'replacement',        // Replacement variant (e.g., newer model)
                'upgrade',            // Upgrade variant (better version)
                'downgrade',          // Downgrade variant (lower tier)
                'accessory',          // Accessory variant (complementary product)
                'bundle_component',   // Component of a bundle
                'compatible',         // Compatible variant
                'alternative',        // Alternative variant
            ])->index();
            $table->string('label')->nullable(); // Custom label for the relationship
            $table->text('description')->nullable(); // Description of the relationship
            $table->integer('sort_order')->default(0)->index(); // Sort order for display
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_bidirectional')->default(false); // If true, creates reverse relationship
            $table->json('metadata')->nullable(); // Additional metadata (e.g., compatibility notes)
            $table->timestamps();

            // Prevent duplicate relationships
            $table->unique(['variant_id', 'related_variant_id', 'relationship_type'], 'variant_relationship_unique');
            
            // Indexes for efficient queries
            $table->index(['variant_id', 'relationship_type', 'is_active']);
            $table->index(['related_variant_id', 'relationship_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_relationships');
    }
};


