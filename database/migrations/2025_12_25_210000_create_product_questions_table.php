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
        Schema::create($this->prefix.'product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->nullOnDelete();
            
            // Customer information (for guest questions)
            $table->string('customer_name')->nullable();
            $table->string('email')->index();
            
            // Question content
            $table->text('question');
            $table->text('question_original')->nullable(); // Store original before moderation
            
            // Status and visibility
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending')->index();
            $table->boolean('is_public')->default(true)->index();
            $table->boolean('is_answered')->default(false)->index();
            
            // Engagement metrics
            $table->integer('views_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            
            // Moderation
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_notes')->nullable();
            
            // Timestamps
            $table->timestamp('asked_at')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'is_answered']);
            $table->index(['status', 'is_public']);
            $table->index(['customer_id', 'product_id']);
            $table->fullText(['question']); // For search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_questions');
    }
};


