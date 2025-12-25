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
