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
        Schema::create($this->prefix.'fit_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->nullOnDelete(); // Verified purchase
            
            // Size information
            $table->string('purchased_size')->index(); // The size they bought
            $table->string('recommended_size')->nullable(); // Size recommended by fit finder
            
            // Body measurements (optional, for algorithm improvement)
            $table->integer('height_cm')->nullable(); // Height in cm
            $table->integer('weight_kg')->nullable(); // Weight in kg
            $table->string('body_type')->nullable(); // e.g., 'slim', 'regular', 'athletic', 'plus'
            
            // Fit feedback
            $table->enum('fit_rating', ['too_small', 'slightly_small', 'perfect', 'slightly_large', 'too_large'])->index();
            $table->boolean('would_recommend_size')->default(true)->index();
            $table->text('fit_notes')->nullable(); // Additional comments
            
            // Specific fit areas (JSON)
            // {
            //   "chest": "perfect",
            //   "waist": "slightly_large",
            //   "hips": "perfect",
            //   "length": "too_short"
            // }
            $table->json('fit_by_area')->nullable();
            
            // Verification
            $table->boolean('is_verified_purchase')->default(false)->index();
            $table->boolean('is_approved')->default(true)->index();
            
            // Timestamps
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'purchased_size']);
            $table->index(['product_id', 'fit_rating']);
            $table->index(['product_id', 'is_verified_purchase']);
            $table->index(['customer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'fit_reviews');
    }
};


