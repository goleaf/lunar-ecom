<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * ProductBadge model for product badges and labels.
 */
class ProductBadge extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_badges';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'handle',
        'type',
        'description',
        'label',
        'color',
        'background_color',
        'border_color',
        'icon',
        'position',
        'style',
        'font_size',
        'padding_x',
        'padding_y',
        'border_radius',
        'show_icon',
        'animated',
        'animation_type',
        'is_active',
        'priority',
        'max_display_count',
        'auto_assign',
        'assignment_rules',
        'display_conditions',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'font_size' => 'integer',
        'padding_x' => 'integer',
        'padding_y' => 'integer',
        'border_radius' => 'integer',
        'show_icon' => 'boolean',
        'animated' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_display_count' => 'integer',
        'auto_assign' => 'boolean',
        'assignment_rules' => 'array',
        'display_conditions' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($badge) {
            if (empty($badge->handle)) {
                $badge->handle = Str::slug($badge->name);
            }
        });
    }

    /**
     * Products relationship.
     *
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Product::class,
            config('lunar.database.table_prefix') . 'product_badge_product',
            'product_badge_id',
            'product_id'
        )->withPivot('is_auto_assigned', 'assigned_at', 'expires_at', 'position', 'priority')
          ->withTimestamps();
    }

    /**
     * Get display label.
     *
     * @return string
     */
    public function getDisplayLabel(): string
    {
        return $this->label ?? $this->name;
    }

    /**
     * Get inline styles.
     *
     * @return string
     */
    public function getInlineStyles(): string
    {
        $styles = [
            'color' => $this->color,
            'background-color' => $this->background_color,
            'font-size' => $this->font_size . 'px',
            'padding' => $this->padding_y . 'px ' . $this->padding_x . 'px',
            'border-radius' => $this->border_radius . 'px',
        ];

        if ($this->border_color) {
            $styles['border'] = '1px solid ' . $this->border_color;
        }

        return implode('; ', array_map(function ($key, $value) {
            return $key . ': ' . $value;
        }, array_keys($styles), $styles));
    }

    /**
     * Get CSS classes.
     *
     * @return string
     */
    public function getCssClasses(): string
    {
        $classes = [
            'product-badge',
            'badge-' . $this->handle,
            'badge-type-' . $this->type,
            'badge-position-' . $this->position,
            'badge-style-' . $this->style,
        ];

        if ($this->animated && $this->animation_type) {
            $classes[] = 'animated';
            $classes[] = 'animate-' . $this->animation_type;
        }

        return implode(' ', $classes);
    }

    /**
     * Check if badge is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get active badges.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope to get auto-assign badges.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutoAssign($query)
    {
        return $query->where('auto_assign', true)->active();
    }

    /**
     * Scope to get badges by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

