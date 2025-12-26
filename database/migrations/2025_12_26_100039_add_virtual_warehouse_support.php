<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds virtual warehouse support for digital goods.
     */
    public function up(): void
    {
        Schema::table($this->prefix.'warehouses', function (Blueprint $table) {
            // Virtual warehouse flag
            if (!Schema::hasColumn($this->prefix.'warehouses', 'is_virtual')) {
                $table->boolean('is_virtual')->default(false)->after('is_dropship')->index();
            }
            
            // Virtual warehouse configuration
            if (!Schema::hasColumn($this->prefix.'warehouses', 'virtual_config')) {
                $table->json('virtual_config')->nullable()->after('is_virtual');
            }
            
            // Geo-distance rules configuration
            if (!Schema::hasColumn($this->prefix.'warehouses', 'geo_distance_rules')) {
                $table->json('geo_distance_rules')->nullable()->after('service_areas');
            }
            
            // Maximum distance for fulfillment (in km)
            if (!Schema::hasColumn($this->prefix.'warehouses', 'max_fulfillment_distance')) {
                $table->decimal('max_fulfillment_distance', 10, 2)->nullable()->after('geo_distance_rules');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'warehouses', function (Blueprint $table) {
            $columns = [
                'is_virtual',
                'virtual_config',
                'geo_distance_rules',
                'max_fulfillment_distance',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'warehouses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


