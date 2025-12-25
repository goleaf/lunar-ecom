<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SeasonalProductRule model for managing seasonal product automation.
 */
class SeasonalProductRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'seasonal_product_rules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'season_tag',
        'name',
        'description',
        'start_date',
        'end_date',
        'timezone',
        'auto_publish',
        'auto_unpublish',
        'days_before_start',
        'days_after_end',
        'applied_to_products',
        'applied_to_categories',
        'applied_to_tags',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_publish' => 'boolean',
        'auto_unpublish' => 'boolean',
        'is_active' => 'boolean',
        'applied_to_products' => 'array',
        'applied_to_categories' => 'array',
        'applied_to_tags' => 'array',
    ];

    /**
     * Check if season is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->setTimezone($this->timezone);
        return $now->between($this->start_date, $this->end_date);
    }

    /**
     * Check if season is upcoming.
     *
     * @return bool
     */
    public function isUpcoming(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->setTimezone($this->timezone);
        return $now->lt($this->start_date);
    }

    /**
     * Check if season has ended.
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        $now = now()->setTimezone($this->timezone);
        return $now->gt($this->end_date);
    }
}

