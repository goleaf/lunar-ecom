<?php

namespace App\Models;

use App\Enums\CollectionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Collection as LunarCollection;

/**
 * Extended Collection model with advanced collection management.
 */
class Collection extends LunarCollection
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Lunar core fields
        'collection_group_id',
        'parent_id',
        'type',
        'sort',
        'attribute_data',

        // Extended collection management fields
        'collection_type',
        'auto_assign',
        'assignment_rules',
        'max_products',
        'sort_by',
        'sort_direction',
        'show_on_homepage',
        'homepage_position',
        'display_style',
        'products_per_row',
        'starts_at',
        'ends_at',
        'scheduled_publish_at',
        'scheduled_unpublish_at',
        'auto_publish_products',
        'product_count',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Keep Lunar's core attribute casting.
        'attribute_data' => \Lunar\Base\Casts\AsAttributeData::class,

        'auto_assign' => 'boolean',
        'assignment_rules' => 'array',
        'max_products' => 'integer',
        'show_on_homepage' => 'boolean',
        'homepage_position' => 'integer',
        'products_per_row' => 'integer',
        'product_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'scheduled_publish_at' => 'datetime',
        'scheduled_unpublish_at' => 'datetime',
        'auto_publish_products' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Products relationship with metadata.
     *
     * @return BelongsToMany
     */
    public function productsWithMetadata(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Product::class,
            config('lunar.database.table_prefix') . 'collection_product_metadata',
            'collection_id',
            'product_id'
        )->withPivot('is_auto_assigned', 'position', 'assigned_at', 'expires_at', 'metadata')
          ->withTimestamps()
          ->orderBy('collection_product_metadata.position')
          ->orderBy('collection_product_metadata.assigned_at', 'desc');
    }

    /**
     * Collection product metadata relationship.
     *
     * @return HasMany
     */
    public function productMetadata(): HasMany
    {
        return $this->hasMany(CollectionProductMetadata::class, 'collection_id');
    }

    /**
     * Smart collection rules relationship.
     *
     * @return HasMany
     */
    public function smartRules(): HasMany
    {
        return $this->hasMany(SmartCollectionRule::class, 'collection_id');
    }

    /**
     * Get sorted products for collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSortedProducts()
    {
        $query = $this->products();

        // Apply sorting
        $sortBy = $this->sort_by ?? 'created_at';
        $sortDirection = $this->sort_direction ?? 'desc';

        return match ($sortBy) {
            'price' => $this->sortByPrice($query, $sortDirection),
            'name' => $query->orderByRaw("JSON_EXTRACT(attribute_data, '$.name.en') {$sortDirection}"),
            'popularity' => $this->sortByPopularity($query, $sortDirection),
            'sales_count' => $this->sortBySalesCount($query, $sortDirection),
            'rating' => $this->sortByRating($query, $sortDirection),
            default => $query->orderBy('created_at', $sortDirection),
        };
    }

    /**
     * Sort products by price.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortByPrice($query, string $direction)
    {
        // This is a simplified version - you may need to join with prices table
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Sort products by popularity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortByPopularity($query, string $direction)
    {
        return $query->orderBy('product_count', $direction)
            ->orderBy('created_at', $direction);
    }

    /**
     * Sort products by sales count.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortBySalesCount($query, string $direction)
    {
        // Join with order lines to count sales
        return $query->withCount([
            'orderLines as sales_count' => function ($q) {
                $q->whereHas('order', function ($orderQuery) {
                    $orderQuery->whereNotNull('placed_at');
                });
            }
        ])->orderBy('sales_count', $direction);
    }

    /**
     * Sort products by rating.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortByRating($query, string $direction)
    {
        return $query->orderBy('average_rating', $direction)
            ->orderBy('total_reviews', $direction);
    }

    /**
     * Check if collection is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get active collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')
              ->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('ends_at')
              ->orWhere('ends_at', '>', now());
        });
    }

    /**
     * Scope to get collections by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('collection_type', $type);
    }

    /**
     * Scope: cross-sell collections.
     */
    public function scopeCrossSell($query)
    {
        return $query->ofType(CollectionType::CROSS_SELL->value);
    }

    /**
     * Scope: up-sell collections.
     */
    public function scopeUpSell($query)
    {
        return $query->ofType(CollectionType::UP_SELL->value);
    }

    /**
     * Schedule this collection for publishing.
     */
    public function schedulePublish(Carbon $publishAt): void
    {
        $this->scheduled_publish_at = $publishAt;
        $this->save();
    }

    /**
     * Schedule this collection for unpublishing.
     */
    public function scheduleUnpublish(Carbon $unpublishAt): void
    {
        $this->scheduled_unpublish_at = $unpublishAt;
        $this->save();
    }

    public function clearScheduledPublish(): void
    {
        $this->scheduled_publish_at = null;
        $this->save();
    }

    public function clearScheduledUnpublish(): void
    {
        $this->scheduled_unpublish_at = null;
        $this->save();
    }

    public function isScheduledForPublish(): bool
    {
        return (bool) $this->scheduled_publish_at;
    }

    public function isScheduledForUnpublish(): bool
    {
        return (bool) $this->scheduled_unpublish_at;
    }

    /**
     * Scope: collections that have any schedule set.
     */
    public function scopeScheduled($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('scheduled_publish_at')
              ->orWhereNotNull('scheduled_unpublish_at');
        });
    }

    /**
     * Scope: collections ready to be published (scheduled_publish_at <= now).
     */
    public function scopeScheduledForPublish($query)
    {
        return $query->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now());
    }

    /**
     * Scope: collections ready to be unpublished (scheduled_unpublish_at <= now).
     */
    public function scopeScheduledForUnpublish($query)
    {
        return $query->whereNotNull('scheduled_unpublish_at')
            ->where('scheduled_unpublish_at', '<=', now());
    }

    /**
     * Scope to get auto-assign collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutoAssign($query)
    {
        return $query->where('auto_assign', true)->active();
    }

    /**
     * Scope to get homepage collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHomepage($query)
    {
        return $query->where('show_on_homepage', true)
            ->active()
            ->orderBy('homepage_position');
    }
}
