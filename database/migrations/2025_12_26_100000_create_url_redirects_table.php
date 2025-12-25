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
        Schema::create($this->prefix.'url_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug')->index();
            $table->string('new_slug')->index();
            $table->string('old_path')->nullable()->index(); // Full path like /products/old-slug
            $table->string('new_path')->nullable()->index(); // Full path like /products/new-slug
            $table->string('redirect_type', 10)->default('301'); // 301 (permanent) or 302 (temporary)
            $table->morphs('redirectable'); // polymorphic: product, category, collection, etc.
            $table->foreignId('language_id')->nullable()->constrained($this->prefix.'languages')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('hit_count')->default(0)->index(); // Track redirect usage
            $table->timestamp('last_hit_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for fast lookups
            $table->index(['old_slug', 'is_active']);
            $table->index(['old_path', 'is_active']);
            // Note: morphs() already creates an index on redirectable_type and redirectable_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'url_redirects');
    }
};

