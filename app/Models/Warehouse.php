<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse model for multi-location inventory management.
 */
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'warehouses';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'phone',
        'email',
        'is_active',
        'priority',
        'notes',
        'latitude',
        'longitude',
        'service_areas',
        'is_dropship',
        'dropship_provider',
        'fulfillment_rules',
        'auto_fulfill',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'service_areas' => 'array',
        'is_dropship' => 'boolean',
        'fulfillment_rules' => 'array',
        'auto_fulfill' => 'boolean',
    ];

    /**
     * Inventory levels relationship.
     *
     * @return HasMany
     */
    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class, 'warehouse_id');
    }

    /**
     * Inventory transactions relationship.
     *
     * @return HasMany
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'warehouse_id');
    }

    /**
     * Stock reservations relationship.
     *
     * @return HasMany
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'warehouse_id');
    }

    /**
     * Fulfillment rules relationship.
     *
     * @return HasMany
     */
    public function fulfillmentRules(): HasMany
    {
        return $this->hasMany(WarehouseFulfillmentRule::class, 'warehouse_id');
    }

    /**
     * Check if warehouse serves location.
     *
     * @param  array  $location
     * @return bool
     */
    public function servesLocation(array $location): bool
    {
        $serviceAreas = $this->service_areas ?? [];

        if (empty($serviceAreas)) {
            return true; // No restrictions
        }

        // Check country
        if (isset($serviceAreas['countries']) && isset($location['country'])) {
            if (!in_array($location['country'], $serviceAreas['countries'])) {
                return false;
            }
        }

        // Check regions
        if (isset($serviceAreas['regions']) && isset($location['region'])) {
            if (!in_array($location['region'], $serviceAreas['regions'])) {
                return false;
            }
        }

        // Check postal codes
        if (isset($serviceAreas['postal_codes']) && isset($location['postcode'])) {
            if (!in_array($location['postcode'], $serviceAreas['postal_codes'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate distance to location.
     *
     * @param  float  $latitude
     * @param  float  $longitude
     * @return float|null Distance in kilometers
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // km

        $dLat = deg2rad($latitude - $this->latitude);
        $dLng = deg2rad($longitude - $this->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Scope to get active warehouses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by priority (lower number = higher priority).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority');
    }

    /**
     * Get full address as string.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postcode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
