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
        $driver = Schema::getConnection()->getDriverName();

        Schema::create($this->prefix.'product_answers', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->foreignId('question_id')->constrained($this->prefix.'product_questions')->cascadeOnDelete();
            
            // Answerer information (polymorphic)
            $table->enum('answerer_type', ['admin', 'customer', 'manufacturer', 'store'])->default('admin')->index();
            $table->unsignedBigInteger('answerer_id')->nullable(); // Can be user_id, customer_id, or manufacturer_id
            
            // Answer content
            $table->text('answer');
            $table->text('answer_original')->nullable(); // Store original before moderation
            
            // Status
            $table->boolean('is_official')->default(false)->index(); // Official answer from store/brand
            $table->boolean('is_approved')->default(true)->index();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->index();
            
            // Engagement
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            
            // Moderation
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_notes')->nullable();
            
            // Timestamps
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->index(['question_id', 'is_approved']);
            $table->index(['answerer_type', 'answerer_id']);
            $table->index(['is_official', 'is_approved']);

            // For search (SQLite schema grammar doesn't support fulltext indexes)
            if ($driver !== 'sqlite') {
                $table->fullText(['answer']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_answers');
    }
};


