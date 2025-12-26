<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for variant attribute dependency rules.
 */
class VariantAttributeDependency extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_attribute_dependencies';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'type',
        'source_option_id',
        'source_value_id',
        'target_option_id',
        'target_value_ids',
        'config',
        'priority',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_value_ids' => 'array',
        'config' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Source option relationship.
     *
     * @return BelongsTo
     */
    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductOption::class, 'source_option_id');
    }

    /**
     * Source value relationship.
     *
     * @return BelongsTo
     */
    public function sourceValue(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductOptionValue::class, 'source_value_id');
    }

    /**
     * Target option relationship.
     *
     * @return BelongsTo
     */
    public function targetOption(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductOption::class, 'target_option_id');
    }

    /**
     * Check if dependency applies to given combination.
     *
     * @param  array  $combination  Array of option_id => value_id
     * @return bool
     */
    public function appliesTo(array $combination): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if source value is in combination
        if ($this->source_value_id) {
            if (!isset($combination[$this->source_option_id]) || 
                $combination[$this->source_option_id] != $this->source_value_id) {
                return false;
            }
        } else {
            // If no specific source value, check if source option is present
            if (!isset($combination[$this->source_option_id])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate combination against this dependency.
     *
     * @param  array  $combination
     * @return array  ['valid' => bool, 'message' => string]
     */
    public function validateCombination(array $combination): array
    {
        if (!$this->appliesTo($combination)) {
            return ['valid' => true, 'message' => null];
        }

        $targetValueId = $combination[$this->target_option_id] ?? null;
        $targetValueIds = $this->target_value_ids ?? [];

        return match($this->type) {
            'requires' => $this->validateRequires($targetValueId, $targetValueIds),
            'excludes' => $this->validateExcludes($targetValueId, $targetValueIds),
            'allows_only' => $this->validateAllowsOnly($targetValueId, $targetValueIds),
            'requires_one_of' => $this->validateRequiresOneOf($combination, $targetValueIds),
            default => ['valid' => true, 'message' => null],
        };
    }

    /**
     * Validate requires rule.
     */
    protected function validateRequires(?int $targetValueId, array $allowedIds): array
    {
        if ($targetValueId === null || empty($allowedIds)) {
            return [
                'valid' => false,
                'message' => $this->config['message'] ?? 'This option requires a value to be selected.',
            ];
        }

        if (!in_array($targetValueId, $allowedIds)) {
            return [
                'valid' => false,
                'message' => $this->config['message'] ?? 'Invalid value for this option.',
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate excludes rule.
     */
    protected function validateExcludes(?int $targetValueId, array $excludedIds): array
    {
        if ($targetValueId !== null && in_array($targetValueId, $excludedIds)) {
            return [
                'valid' => false,
                'message' => $this->config['message'] ?? 'This value cannot be selected with the current combination.',
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate allows_only rule.
     */
    protected function validateAllowsOnly(?int $targetValueId, array $allowedIds): array
    {
        if ($targetValueId !== null && !in_array($targetValueId, $allowedIds)) {
            return [
                'valid' => false,
                'message' => $this->config['message'] ?? 'Only specific values are allowed for this option.',
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate requires_one_of rule.
     */
    protected function validateRequiresOneOf(array $combination, array $requiredIds): array
    {
        $found = false;
        foreach ($requiredIds as $valueId) {
            if (in_array($valueId, $combination)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return [
                'valid' => false,
                'message' => $this->config['message'] ?? 'At least one of the required values must be selected.',
            ];
        }

        return ['valid' => true, 'message' => null];
    }
}


