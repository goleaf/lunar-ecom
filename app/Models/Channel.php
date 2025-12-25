<?php

namespace App\Models;

use Lunar\Models\Channel as LunarChannel;

/**
 * Extended Channel model with marketplace and geo-restriction support.
 */
class Channel extends LunarChannel
{
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'marketplace_config' => 'array',
        'allowed_countries' => 'array',
        'blocked_countries' => 'array',
        'allowed_regions' => 'array',
        'is_active' => 'boolean',
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Channel-specific product data relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channelProductData()
    {
        return $this->hasMany(\App\Models\ChannelProductData::class, 'channel_id');
    }

    /**
     * Channel-specific media relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channelMedia()
    {
        return $this->hasMany(\App\Models\ChannelMedia::class, 'channel_id');
    }

    /**
     * Default currency relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultCurrency()
    {
        return $this->belongsTo(\Lunar\Models\Currency::class, 'default_currency_id');
    }

    /**
     * Default language relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultLanguage()
    {
        return $this->belongsTo(\Lunar\Models\Language::class, 'default_language_id');
    }

    /**
     * Check if channel is a marketplace.
     *
     * @return bool
     */
    public function isMarketplace(): bool
    {
        return $this->marketplace_type && $this->marketplace_type !== 'webstore';
    }

    /**
     * Get marketplace configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getMarketplaceConfig(string $key, $default = null)
    {
        return $this->marketplace_config[$key] ?? $default;
    }

    /**
     * Set marketplace configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setMarketplaceConfig(string $key, $value)
    {
        $config = $this->marketplace_config ?? [];
        $config[$key] = $value;
        $this->marketplace_config = $config;
        return $this;
    }

    /**
     * Scope to get active channels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get marketplace channels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMarketplaces($query)
    {
        return $query->whereNotNull('marketplace_type')
            ->where('marketplace_type', '!=', 'webstore');
    }

    /**
     * Scope to get channels with sync enabled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSyncEnabled($query)
    {
        return $query->where('sync_enabled', true);
    }
}

