<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for channel-specific media assignments.
     * Allows different images/media per channel (e.g., different product images for web vs mobile).
     */
    public function up(): void
    {
        Schema::create($this->prefix.'channel_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained($this->prefix.'channels')
                ->onDelete('cascade');
            $table->morphs('mediable'); // product, collection, brand, etc.
            $table->unsignedBigInteger('media_id'); // Spatie Media Library media ID
            $table->string('collection_name')->default('default'); // Media collection name
            $table->integer('position')->default(0)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->json('alt_text')->nullable(); // Translatable alt text
            $table->json('caption')->nullable(); // Translatable caption
            $table->timestamps();
            
            // Unique constraint: prevent duplicate media assignments
            $table->unique(['channel_id', 'mediable_type', 'mediable_id', 'media_id', 'collection_name']);
            
            // Indexes for efficient queries
            $table->index(['channel_id', 'mediable_type', 'mediable_id', 'collection_name']);
            $table->index(['channel_id', 'mediable_type', 'mediable_id', 'is_primary']);
            $table->index(['channel_id', 'mediable_type', 'mediable_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'channel_media');
    }
};

