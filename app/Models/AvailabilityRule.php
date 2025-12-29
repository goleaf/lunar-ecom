<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class AvailabilityRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'availability_rules';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'rule_type',
        'rule_config',
        'rule_start_date',
        'rule_end_date',
        'priority',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'rule_config' => 'array',
        'rule_start_date' => 'date',
        'rule_end_date' => 'date',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get minimum rental period in days.
     */
    public function getMinimumDays(): ?int
    {
        if ($this->rule_type === 'minimum_rental_period') {
            return $this->rule_config['minimum_days'] ?? null;
        }
        return null;
    }

    /**
     * Get maximum rental period in days.
     */
    public function getMaximumDays(): ?int
    {
        if ($this->rule_type === 'maximum_rental_period') {
            return $this->rule_config['maximum_days'] ?? null;
        }
        return null;
    }

    /**
     * Get lead time in hours.
     */
    public function getLeadTimeHours(): ?int
    {
        if ($this->rule_type === 'lead_time') {
            return $this->rule_config['lead_time_hours'] ?? null;
        }
        return null;
    }

    /**
     * Get buffer time in hours.
     */
    public function getBufferHours(): ?int
    {
        if ($this->rule_type === 'buffer_time') {
            return $this->rule_config['buffer_hours'] ?? null;
        }
        return null;
    }
}


