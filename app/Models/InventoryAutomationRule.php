<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory Automation Rule model.
 * 
 * Defines automation rules for inventory management:
 * - Auto-disable/enable variants
 * - Trigger alerts
 * - Create reorders
 */
class InventoryAutomationRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'inventory_automation_rules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'product_id',
        'name',
        'description',
        'trigger_type',
        'trigger_conditions',
        'action_type',
        'action_config',
        'is_active',
        'priority',
        'run_once',
        'cooldown_minutes',
        'last_triggered_at',
        'trigger_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trigger_conditions' => 'array',
        'action_config' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'run_once' => 'boolean',
        'cooldown_minutes' => 'integer',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
    ];

    /**
     * Product variant relationship.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductVariant::class);
    }

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Product::class);
    }

    /**
     * Scope active rules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by trigger type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTriggerType($query, string $type)
    {
        return $query->where('trigger_type', $type);
    }

    /**
     * Scope by action type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActionType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Check if rule can be triggered (cooldown check).
     *
     * @return bool
     */
    public function canTrigger(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->run_once && $this->trigger_count > 0) {
            return false;
        }

        if ($this->cooldown_minutes && $this->last_triggered_at) {
            $cooldownEnd = $this->last_triggered_at->addMinutes($this->cooldown_minutes);
            if (now()->isBefore($cooldownEnd)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark rule as triggered.
     *
     * @return void
     */
    public function markTriggered(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1,
        ]);
    }
}


