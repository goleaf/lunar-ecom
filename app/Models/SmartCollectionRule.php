<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Smart Collection Rule model.
 */
class SmartCollectionRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'smart_collection_rules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'field',
        'operator',
        'value',
        'logic_group',
        'group_operator',
        'priority',
        'is_active',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the collection that owns the rule.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get available fields for rules.
     *
     * @return array
     */
    public static function getAvailableFields(): array
    {
        return [
            'price' => [
                'label' => 'Price',
                'operators' => ['greater_than', 'less_than', 'between', 'equals'],
                'value_type' => 'number',
            ],
            'tag' => [
                'label' => 'Tag',
                'operators' => ['equals', 'not_equals', 'in', 'not_in', 'contains'],
                'value_type' => 'text',
            ],
            'product_type' => [
                'label' => 'Product Type',
                'operators' => ['equals', 'not_equals', 'in', 'not_in'],
                'value_type' => 'select',
            ],
            'inventory_status' => [
                'label' => 'Inventory Status',
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'select',
            ],
            'brand' => [
                'label' => 'Brand',
                'operators' => ['equals', 'not_equals', 'in', 'not_in'],
                'value_type' => 'select',
            ],
            'category' => [
                'label' => 'Category',
                'operators' => ['equals', 'not_equals', 'in', 'not_in'],
                'value_type' => 'select',
            ],
            'attribute' => [
                'label' => 'Attribute',
                'operators' => ['equals', 'not_equals', 'in', 'not_in', 'contains'],
                'value_type' => 'attribute',
            ],
            'rating' => [
                'label' => 'Rating',
                'operators' => ['greater_than', 'less_than', 'equals', 'between'],
                'value_type' => 'number',
            ],
            'created_at' => [
                'label' => 'Created Date',
                'operators' => ['greater_than', 'less_than', 'between', 'equals'],
                'value_type' => 'date',
            ],
            'updated_at' => [
                'label' => 'Updated Date',
                'operators' => ['greater_than', 'less_than', 'between', 'equals'],
                'value_type' => 'date',
            ],
        ];
    }

    /**
     * Get available operators.
     *
     * @return array
     */
    public static function getAvailableOperators(): array
    {
        return [
            'equals' => 'Equals',
            'not_equals' => 'Does not equal',
            'greater_than' => 'Greater than',
            'less_than' => 'Less than',
            'between' => 'Between',
            'contains' => 'Contains',
            'not_contains' => 'Does not contain',
            'in' => 'Is one of',
            'not_in' => 'Is not one of',
            'is_null' => 'Is empty',
            'is_not_null' => 'Is not empty',
        ];
    }
}

