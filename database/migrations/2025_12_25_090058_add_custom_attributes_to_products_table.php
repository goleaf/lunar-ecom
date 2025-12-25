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
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            // SKU - unique, indexed
            $table->string('sku')->unique()->nullable()->after('id');
            
            // Barcode (EAN-13) - indexed for search
            $table->string('barcode', 13)->nullable()->index()->after('sku');
            
            // Weight in grams
            $table->unsignedInteger('weight')->nullable()->after('barcode');
            
            // Dimensions in cm
            $table->decimal('length', 8, 2)->nullable()->after('weight');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            
            // Manufacturer name - indexed for search
            $table->string('manufacturer_name')->nullable()->index()->after('height');
            
            // Warranty period in months
            $table->unsignedSmallInteger('warranty_period')->nullable()->after('manufacturer_name');
            
            // Condition (new/refurbished/used) - indexed for filtering
            $table->enum('condition', ['new', 'refurbished', 'used'])->nullable()->index()->after('warranty_period');
            
            // Origin country - indexed for search
            $table->string('origin_country', 2)->nullable()->index()->after('condition');
            
            // JSON field for unlimited custom meta fields
            $table->json('custom_meta')->nullable()->after('origin_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'barcode',
                'weight',
                'length',
                'width',
                'height',
                'manufacturer_name',
                'warranty_period',
                'condition',
                'origin_country',
                'custom_meta',
            ]);
        });
    }
};
