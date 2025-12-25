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
        Schema::create($this->prefix.'product_qa_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            
            // Question metrics
            $table->integer('total_questions')->default(0);
            $table->integer('approved_questions')->default(0);
            $table->integer('pending_questions')->default(0);
            $table->integer('answered_questions')->default(0);
            $table->integer('unanswered_questions')->default(0);
            
            // Answer metrics
            $table->integer('total_answers')->default(0);
            $table->integer('official_answers')->default(0);
            $table->integer('customer_answers')->default(0);
            
            // Performance metrics
            $table->decimal('average_response_time_hours', 10, 2)->nullable(); // Average time to answer
            $table->decimal('answer_rate', 5, 2)->default(0); // Percentage of questions answered
            $table->decimal('satisfaction_score', 3, 2)->nullable(); // Based on helpful votes
            
            // Engagement metrics
            $table->integer('total_views')->default(0);
            $table->integer('total_helpful_votes')->default(0);
            
            // Time period
            $table->date('period_start');
            $table->date('period_end');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'period_start', 'period_end']);
            $table->unique(['product_id', 'period_start', 'period_end'], 'product_qa_metrics_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_qa_metrics');
    }
};

