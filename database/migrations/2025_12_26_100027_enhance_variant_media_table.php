<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances variant media table with:
     * - Media type (image, video, 360, 3D/AR)
     * - Channel support
     * - Locale support
     * - Alt text & accessibility metadata
     * - Sort order per variant
     */
    public function up(): void
    {
        Schema::table($this->prefix.'media_product_variant', function (Blueprint $table) {
            // Media type
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'media_type')) {
                $table->enum('media_type', [
                    'image',
                    'video',
                    'image_360',
                    'model_3d',
                    'ar_file',
                    'document',
                ])->default('image')->index()->after('product_variant_id');
            }
            
            // Channel-specific media
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'channel_id')) {
                $table->foreignId('channel_id')
                    ->nullable()
                    ->constrained($this->prefix.'channels')
                    ->nullOnDelete()
                    ->after('media_type');
            }
            
            // Locale-specific media
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'locale')) {
                $table->string('locale', 10)->nullable()->index()->after('channel_id');
            }
            
            // Alt text (translatable)
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'alt_text')) {
                $table->json('alt_text')->nullable()->after('locale');
            }
            
            // Caption (translatable)
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'caption')) {
                $table->json('caption')->nullable()->after('alt_text');
            }
            
            // Accessibility metadata
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'accessibility_metadata')) {
                $table->json('accessibility_metadata')->nullable()->after('caption');
            }
            
            // Media metadata (for 3D/AR files)
            if (!Schema::hasColumn($this->prefix.'media_product_variant', 'media_metadata')) {
                $table->json('media_metadata')->nullable()->after('accessibility_metadata');
            }
            
            // Indexes
            $table->index(['product_variant_id', 'media_type', 'primary']);
            $table->index(['product_variant_id', 'channel_id', 'locale']);
            $table->index(['product_variant_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'media_product_variant', function (Blueprint $table) {
            $columns = [
                'media_type',
                'channel_id',
                'locale',
                'alt_text',
                'caption',
                'accessibility_metadata',
                'media_metadata',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'media_product_variant', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop indexes
            if (Schema::hasColumn($this->prefix.'media_product_variant', 'media_type')) {
                $table->dropIndex(['product_variant_id', 'media_type', 'primary']);
            }
            if (Schema::hasColumn($this->prefix.'media_product_variant', 'channel_id')) {
                $table->dropIndex(['product_variant_id', 'channel_id', 'locale']);
            }
        });
    }
};

