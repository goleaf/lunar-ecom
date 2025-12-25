<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for product workflow history/audit trail.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->foreignId('workflow_id')
                ->nullable()
                ->constrained($this->prefix.'product_workflows')
                ->nullOnDelete();
            
            // Action details
            $table->enum('action', [
                'created',
                'updated',
                'submitted',
                'approved',
                'rejected',
                'published',
                'unpublished',
                'archived',
                'unarchived',
                'expired',
                'auto_archived',
                'bulk_action',
            ])->index();
            
            // Status transition
            $table->enum('from_status', ['draft', 'review', 'approved', 'published', 'archived', 'rejected'])
                ->nullable();
            $table->enum('to_status', ['draft', 'review', 'approved', 'published', 'archived', 'rejected'])
                ->nullable();
            
            // User who performed action
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            
            // Additional data
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store additional context
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['product_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_workflow_history');
    }
};

