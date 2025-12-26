<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds core variant fields for SKU-level management:
     * - UUID/ULID identifier
     * - GTIN/EAN/UPC/ISBN fields
     * - Internal reference code
     * - Status field (active, inactive, archived)
     * - Visibility field (public, hidden, channel-specific)
     * - Soft delete support
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // UUID/ULID identifier (if not using auto-increment)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable()->after('id');
            }
            
            // GTIN/EAN/UPC/ISBN fields
            if (!Schema::hasColumn($this->prefix.'product_variants', 'gtin')) {
                $table->string('gtin', 14)->nullable()->index()->after('barcode');
            }
            if (!Schema::hasColumn($this->prefix.'product_variants', 'ean')) {
                $table->string('ean', 13)->nullable()->index()->after('gtin');
            }
            if (!Schema::hasColumn($this->prefix.'product_variants', 'upc')) {
                $table->string('upc', 12)->nullable()->index()->after('ean');
            }
            if (!Schema::hasColumn($this->prefix.'product_variants', 'isbn')) {
                $table->string('isbn', 13)->nullable()->index()->after('upc');
            }
            
            // Internal reference code
            if (!Schema::hasColumn($this->prefix.'product_variants', 'internal_reference')) {
                $table->string('internal_reference', 100)->nullable()->index()->after('isbn');
            }
            
            // Status field (active, inactive, archived)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'status')) {
                $table->enum('status', ['active', 'inactive', 'archived'])
                    ->default('active')
                    ->index()
                    ->after('enabled');
            }
            
            // Visibility field (public, hidden, channel-specific)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'visibility')) {
                $table->enum('visibility', ['public', 'hidden', 'channel_specific'])
                    ->default('public')
                    ->index()
                    ->after('status');
            }
            
            // Channel-specific visibility (JSON)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'channel_visibility')) {
                $table->json('channel_visibility')->nullable()->after('visibility');
            }
            
            // Variant title (manual override)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'title')) {
                $table->string('title', 255)->nullable()->after('variant_name');
            }
            
            // SKU format configuration (JSON)
            if (!Schema::hasColumn($this->prefix.'product_variants', 'sku_format')) {
                $table->json('sku_format')->nullable()->after('sku');
            }
            
            // Soft delete support
            if (!Schema::hasColumn($this->prefix.'product_variants', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $columns = [
                'uuid',
                'gtin',
                'ean',
                'upc',
                'isbn',
                'internal_reference',
                'status',
                'visibility',
                'channel_visibility',
                'title',
                'sku_format',
                'deleted_at',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'product_variants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


