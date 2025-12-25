<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Language;

/**
 * URL Redirect model for managing old slug redirects.
 * 
 * Supports 301 (permanent) and 302 (temporary) redirects.
 * Tracks redirect usage for analytics.
 */
class UrlRedirect extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'url_redirects';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'old_slug',
        'new_slug',
        'old_path',
        'new_path',
        'redirect_type',
        'redirectable_type',
        'redirectable_id',
        'language_id',
        'is_active',
        'hit_count',
        'last_hit_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    /**
     * Redirectable relationship (polymorphic).
     *
     * @return MorphTo
     */
    public function redirectable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Language relationship.
     *
     * @return BelongsTo
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Scope to get active redirects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get permanent redirects (301).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePermanent($query)
    {
        return $query->where('redirect_type', '301');
    }

    /**
     * Scope to get temporary redirects (302).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTemporary($query)
    {
        return $query->where('redirect_type', '302');
    }

    /**
     * Scope to find redirect by old slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOldSlug($query, string $slug)
    {
        return $query->where('old_slug', $slug);
    }

    /**
     * Scope to find redirect by old path.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $path
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOldPath($query, string $path)
    {
        return $query->where('old_path', $path);
    }

    /**
     * Increment hit count and update last hit timestamp.
     *
     * @return $this
     */
    public function recordHit()
    {
        $this->increment('hit_count');
        $this->update(['last_hit_at' => now()]);
        
        return $this;
    }

    /**
     * Check if redirect is permanent.
     *
     * @return bool
     */
    public function isPermanent(): bool
    {
        return $this->redirect_type === '301';
    }

    /**
     * Check if redirect is temporary.
     *
     * @return bool
     */
    public function isTemporary(): bool
    {
        return $this->redirect_type === '302';
    }
}

