<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = config('lunar.database.table_prefix');
        
        Schema::create('fit_feedbacks', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('product_id')->constrained($prefix.'products')->cascadeOnDelete();
            $table->foreignId('size_guide_id')->nullable()->constrained('size_guides')->nullOnDelete();
            $table->foreignId('fit_finder_quiz_id')->nullable()->constrained('fit_finder_quizzes')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained($prefix.'customers')->nullOnDelete();
            $table->string('order_id')->nullable(); // Optional: link to order if available
            $table->string('purchased_size')->nullable();
            $table->string('recommended_size')->nullable(); // Size recommended by fit finder
            $table->enum('actual_fit', ['perfect', 'too_small', 'too_large', 'too_tight', 'too_loose'])->nullable();
            $table->tinyInteger('fit_rating')->nullable(); // 1-5 rating
            $table->json('body_measurements')->nullable(); // Customer measurements
            $table->text('feedback_text')->nullable();
            $table->boolean('would_exchange')->default(false);
            $table->boolean('would_return')->default(false);
            $table->boolean('is_helpful')->default(false); // Admin can mark if feedback is helpful
            $table->boolean('is_public')->default(false); // Can be shown to other customers
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('customer_id');
            $table->index('fit_rating');
            $table->index('actual_fit');
            $table->index('is_helpful');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_feedbacks');
    }
};
