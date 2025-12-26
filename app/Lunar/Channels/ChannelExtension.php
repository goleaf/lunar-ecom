<?php

namespace App\Lunar\Channels;

use Lunar\Models\Channel;
use App\Models\ChannelWarehouse;

/**
 * Channel Extension.
 * 
 * Adds warehouse relationships to Lunar Channel model.
 */
class ChannelExtension
{
    /**
     * Boot the extension.
     */
    public static function boot(): void
    {
        // Add dynamic relationship
        Channel::resolveRelationUsing('warehouses', function ($channel) {
            return $channel->belongsToMany(
                \App\Models\Warehouse::class,
                config('lunar.database.table_prefix') . 'channel_warehouse',
                'channel_id',
                'warehouse_id'
            )
            ->withPivot(['priority', 'is_default', 'is_active', 'fulfillment_rules'])
            ->withTimestamps();
        });

        Channel::resolveRelationUsing('warehouseMappings', function ($channel) {
            return $channel->hasMany(
                ChannelWarehouse::class,
                'channel_id'
            );
        });
    }
}


