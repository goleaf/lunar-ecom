<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PriceMatrixRule model (rules attached to a price matrix).
 *
 * Note: This maps to the `pricing_rules` table created for price matrices.
 */
class PriceMatrixRule extends Model
{
    use HasFactory;

    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'pricing_rules';
    }

    protected $fillable = [
        'price_matrix_id',
        'rule_type',
        'rule_key',
        'operator',
        'rule_value',
        'price',
        'price_adjustment',
        'percentage_discount',
        'adjustment_type',
        'priority',
        'conditions',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_adjustment' => 'decimal:2',
        'percentage_discount' => 'integer',
        'priority' => 'integer',
        'conditions' => 'array',
    ];

    public function priceMatrix(): BelongsTo
    {
        return $this->belongsTo(PriceMatrix::class, 'price_matrix_id');
    }

    /**
     * Check whether this rule matches the given context.
     *
     * Supported operators: =, !=, >, >=, <, <=, in, not_in, between
     */
    public function matches(array $context = []): bool
    {
        $key = $this->rule_key ?: $this->rule_type;

        $contextValue = $this->resolveContextValue($key, $context);

        if ($contextValue === null) {
            return false;
        }

        $expected = $this->parseRuleValue($this->rule_value);

        return match ($this->operator) {
            '=' => $contextValue == $expected,
            '!=' => $contextValue != $expected,
            '>' => is_numeric($contextValue) && is_numeric($expected) && $contextValue > $expected,
            '>=' => is_numeric($contextValue) && is_numeric($expected) && $contextValue >= $expected,
            '<' => is_numeric($contextValue) && is_numeric($expected) && $contextValue < $expected,
            '<=' => is_numeric($contextValue) && is_numeric($expected) && $contextValue <= $expected,
            'in' => in_array($contextValue, (array) $expected, true),
            'not_in' => ! in_array($contextValue, (array) $expected, true),
            'between' => $this->matchesBetween($contextValue, $expected),
            default => false,
        };
    }

    protected function resolveContextValue(string $key, array $context): mixed
    {
        if (array_key_exists($key, $context)) {
            return $context[$key];
        }

        return match ($key) {
            'customer_group', 'customer_group_id' => $context['customer_group'] ?? $context['customer_group_id'] ?? null,
            'region', 'country', 'country_code' => $context['region'] ?? $context['country'] ?? $context['country_code'] ?? null,
            'quantity' => $context['quantity'] ?? null,
            default => $context[$this->rule_type] ?? null,
        };
    }

    protected function parseRuleValue(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        // Try JSON first (arrays / objects).
        if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Comma-separated list for "in" operators.
        if (str_contains($trimmed, ',') && in_array($this->operator, ['in', 'not_in'], true)) {
            return array_values(array_filter(array_map('trim', explode(',', $trimmed)), fn ($v) => $v !== ''));
        }

        // Numeric values
        if (is_numeric($trimmed)) {
            return $trimmed + 0;
        }

        return $trimmed;
    }

    protected function matchesBetween(mixed $contextValue, mixed $expected): bool
    {
        if (! is_numeric($contextValue)) {
            return false;
        }

        if (is_array($expected) && count($expected) === 2 && is_numeric($expected[0]) && is_numeric($expected[1])) {
            return $contextValue >= $expected[0] && $contextValue <= $expected[1];
        }

        return false;
    }
}

