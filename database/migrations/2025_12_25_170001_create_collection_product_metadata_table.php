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
        Schema::create($this->prefix.'collection_product_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained($this->prefix.'collections')->onDelete('cascade');
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Assignment details
            $table->boolean('is_auto_assigned')->default(false)->index();
            $table->integer('position')->default(0)->index(); // Manual sorting position
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional metadata
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['collection_id', 'product_id'], 'unique_collection_product');
            
            // Indexes
            $table->index(['collection_id', 'position']);
            $table->index(['collection_id', 'is_auto_assigned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'collection_product_metadata');
    }
};

