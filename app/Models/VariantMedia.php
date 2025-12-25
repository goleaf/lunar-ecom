<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model for variant media with enhanced metadata.
 */
class VariantMedia extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'media_product_variant';
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'media_id',
        'product_variant_id',
        'media_type',
        'channel_id',
        'locale',
        'primary',
        'position',
        'alt_text',
        'caption',
        'accessibility_metadata',
        'media_metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'primary' => 'boolean',
        'position' => 'integer',
        'alt_text' => 'array',
        'caption' => 'array',
        'accessibility_metadata' => 'array',
        'media_metadata' => 'array',
    ];

    /**
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Media relationship.
     *
     * @return BelongsTo
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
    }

    /**
     * Channel relationship.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Channel::class);
    }

    /**
     * Get alt text for locale.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getAltText(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $altText = $this->alt_text ?? [];
        
        return $altText[$locale] ?? $altText['en'] ?? null;
    }

    /**
     * Get caption for locale.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getCaption(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $caption = $this->caption ?? [];
        
        return $caption[$locale] ?? $caption['en'] ?? null;
    }
}

