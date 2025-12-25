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
        Schema::create($this->prefix.'reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->onDelete('set null');
            
            // Rating and content
            $table->unsignedTinyInteger('rating')->index(); // 1-5 stars
            $table->string('title', 255);
            $table->text('content'); // Min 10 chars, max 5000 chars
            $table->json('pros')->nullable(); // Array of pros
            $table->json('cons')->nullable(); // Array of cons
            $table->boolean('recommended')->default(true)->index();
            
            // Moderation
            $table->boolean('is_approved')->default(false)->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Verification
            $table->boolean('is_verified_purchase')->default(false)->index();
            
            // Helpful votes
            $table->unsignedInteger('helpful_count')->default(0)->index();
            $table->unsignedInteger('not_helpful_count')->default(0);
            
            // Admin response
            $table->text('admin_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Reporting
            $table->unsignedInteger('report_count')->default(0);
            $table->boolean('is_reported')->default(false)->index();
            
            // Spam prevention
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['product_id', 'is_approved', 'rating']);
            $table->index(['customer_id', 'product_id']); // Prevent duplicate reviews
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'reviews');
    }
};
