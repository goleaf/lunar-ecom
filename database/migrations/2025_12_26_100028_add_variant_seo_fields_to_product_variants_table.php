<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds SEO fields for variants:
     * - Variant-specific URL slug (optional)
     * - Canonical URL override
     * - Robots meta (index/noindex)
     * - OpenGraph metadata
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Variant-specific URL slug (optional)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'url_slug')) {
                $table->string('url_slug', 255)->nullable()->unique()->index()->after('meta_keywords');
            }
            
            // Canonical URL override
            if (!Schema::hasColumn($this->prefix.'product_variants', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('url_slug');
            }
            
            // Canonical inheritance rule
            if (!Schema::hasColumn($this->prefix.'product_variants', 'canonical_inheritance')) {
                $table->enum('canonical_inheritance', [
                    'inherit',      // Inherit from product
                    'override',     // Use variant-specific canonical
                    'none'          // No canonical (rare)
                ])->default('inherit')->index()->after('canonical_url');
            }
            
            // Robots meta (index/noindex)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'robots_meta')) {
                $table->string('robots_meta', 50)->nullable()->index()->after('canonical_inheritance');
            }
            
            // OpenGraph title override
            if (!Schema::hasColumn($this->prefix.'product_variants', 'og_title')) {
                $table->string('og_title', 255)->nullable()->after('robots_meta');
            }
            
            // OpenGraph description override
            if (!Schema::hasColumn($this->prefix.'product_variants', 'og_description')) {
                $table->text('og_description')->nullable()->after('og_title');
            }
            
            // OpenGraph image override (media ID)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'og_image_id')) {
                $table->unsignedBigInteger('og_image_id')->nullable()->after('og_description');
            }
            
            // Twitter Card type
            if (!Schema::hasColumn($this->prefix.'product_variants', 'twitter_card')) {
                $table->enum('twitter_card', [
                    'summary',
                    'summary_large_image',
                    'app',
                    'player'
                ])->default('summary_large_image')->after('og_image_id');
            }
            
            // Index for SEO queries
            $table->index(['robots_meta', 'canonical_inheritance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $columns = [
                'url_slug',
                'canonical_url',
                'canonical_inheritance',
                'robots_meta',
                'og_title',
                'og_description',
                'og_image_id',
                'twitter_card',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'product_variants', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop indexes
            if (Schema::hasColumn($this->prefix.'product_variants', 'robots_meta')) {
                $table->dropIndex(['robots_meta', 'canonical_inheritance']);
            }
        });
    }
};

