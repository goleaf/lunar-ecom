<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds fulfillment and geo-based fields to warehouses:
     * - Geo-based selection (latitude/longitude, service areas)
     * - Drop-shipping support
     * - Fulfillment priority rules
     * - Service areas (countries/regions)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'warehouses', function (Blueprint $table) {
            // Geo-location for geo-based selection
            if (!Schema::hasColumn($this->prefix.'warehouses', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('country');
            }
            if (!Schema::hasColumn($this->prefix.'warehouses', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            
            // Service areas (JSON: countries, regions, postal codes)
            if (!Schema::hasColumn($this->prefix.'warehouses', 'service_areas')) {
                $table->json('service_areas')->nullable()->after('longitude');
            }
            
            // Drop-shipping support
            if (!Schema::hasColumn($this->prefix.'warehouses', 'is_dropship')) {
                $table->boolean('is_dropship')->default(false)->index()->after('service_areas');
            }
            
            // Drop-shipper information
            if (!Schema::hasColumn($this->prefix.'warehouses', 'dropship_provider')) {
                $table->string('dropship_provider', 100)->nullable()->after('is_dropship');
            }
            
            // Fulfillment priority rules (JSON)
            if (!Schema::hasColumn($this->prefix.'warehouses', 'fulfillment_rules')) {
                $table->json('fulfillment_rules')->nullable()->after('dropship_provider');
            }
            
            // Auto-fulfillment enabled
            if (!Schema::hasColumn($this->prefix.'warehouses', 'auto_fulfill')) {
                $table->boolean('auto_fulfill')->default(true)->after('fulfillment_rules');
            }
            
            // Indexes
            $table->index(['is_dropship', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'warehouses', function (Blueprint $table) {
            $columns = [
                'latitude',
                'longitude',
                'service_areas',
                'is_dropship',
                'dropship_provider',
                'fulfillment_rules',
                'auto_fulfill',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'warehouses', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop indexes
            if (Schema::hasColumn($this->prefix.'warehouses', 'is_dropship')) {
                $table->dropIndex(['is_dropship', 'is_active']);
            }
            if (Schema::hasColumn($this->prefix.'warehouses', 'latitude')) {
                $table->dropIndex(['latitude', 'longitude']);
            }
        });
    }
};


