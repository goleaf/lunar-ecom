<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\B2BContract;

/**
 * Contract Rule Model
 * 
 * Represents rules that apply to a B2B contract.
 * Rules can override promotions, payment methods, shipping, discounts, etc.
 */
class ContractRule extends Model
{
    use HasFactory;

    protected $table = 'lunar_contract_rules';

    protected $fillable = [
        'contract_id',
        'rule_type',
        'name',
        'description',
        'is_active',
        'priority',
        'conditions',
        'actions',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'conditions' => 'array',
        'actions' => 'array',
        'meta' => 'array',
    ];

    // Rule type constants
    const TYPE_PRICE_OVERRIDE = 'price_override';
    const TYPE_PROMOTION_OVERRIDE = 'promotion_override';
    const TYPE_PAYMENT_METHOD = 'payment_method';
    const TYPE_SHIPPING = 'shipping';
    const TYPE_DISCOUNT = 'discount';

    /**
     * Get the contract that owns this rule.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Check if rule matches given conditions.
     * 
     * @param array $context Context data (cart, order, etc.)
     * @return bool
     */
    public function matches(array $context): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $conditions = $this->conditions ?? [];

        if (empty($conditions)) {
            return true; // No conditions = always matches
        }

        // Check each condition
        foreach ($conditions as $key => $value) {
            $contextValue = $context[$key] ?? null;

            switch ($key) {
                case 'cart_total':
                    if (isset($value['min']) && $contextValue < $value['min']) {
                        return false;
                    }
                    if (isset($value['max']) && $contextValue > $value['max']) {
                        return false;
                    }
                    break;

                case 'product_categories':
                    if (is_array($value) && is_array($contextValue)) {
                        if (empty(array_intersect($value, $contextValue))) {
                            return false;
                        }
                    }
                    break;

                case 'quantity':
                    if (isset($value['min']) && $contextValue < $value['min']) {
                        return false;
                    }
                    if (isset($value['max']) && $contextValue > $value['max']) {
                        return false;
                    }
                    break;

                default:
                    // Simple equality check
                    if ($contextValue !== $value) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Get rule actions.
     */
    public function getActions(): array
    {
        return $this->actions ?? [];
    }

    /**
     * Scope to get active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rules by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope to get rules by priority (highest first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}

