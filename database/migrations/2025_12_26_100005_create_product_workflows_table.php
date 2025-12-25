<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for product workflow states and transitions.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            
            // Workflow state
            $table->enum('status', ['draft', 'review', 'approved', 'published', 'archived', 'rejected'])
                ->default('draft')
                ->index();
            
            // Previous status for history
            $table->enum('previous_status', ['draft', 'review', 'approved', 'published', 'archived', 'rejected'])
                ->nullable();
            
            // Approval information
            $table->foreignId('submitted_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()
                ->constrained('users')->nullOnDelete();
            
            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            
            // Comments/notes
            $table->text('submission_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Expiration
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('auto_archive_on_expiry')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'submitted_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_workflows');
    }
};

