<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for tracking bulk actions on products.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_bulk_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Action type
            $table->enum('action_type', [
                'publish',
                'unpublish',
                'archive',
                'unarchive',
                'delete',
                'update_status',
                'update_price',
                'update_stock',
                'assign_category',
                'assign_collection',
                'assign_tag',
                'remove_category',
                'remove_collection',
                'remove_tag',
            ])->index();
            
            // Filters (JSON) - what products were selected
            $table->json('filters')->nullable();
            
            // Action parameters (JSON)
            $table->json('parameters')->nullable();
            // Example: {"status": "published", "price": 1000, "category_id": 5}
            
            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->index();
            
            // Progress tracking
            $table->integer('total_products')->default(0);
            $table->integer('processed_products')->default(0);
            $table->integer('successful_products')->default(0);
            $table->integer('failed_products')->default(0);
            
            // Results
            $table->json('product_ids')->nullable(); // Affected product IDs
            $table->json('errors')->nullable(); // Error details
            
            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_bulk_actions');
    }
};

