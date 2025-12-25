<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Channel;

/**
 * ChannelAttributeValue model for storing channel-specific attribute values.
 * 
 * Allows different attribute values per channel (e.g., different descriptions per market).
 * Useful for multi-channel/multi-market scenarios where content needs to be localized per channel.
 */
class ChannelAttributeValue extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'channel_attribute_values';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'channel_id',
        'attribute_id',
        'value',
        'numeric_value',
        'text_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
        'numeric_value' => 'decimal:4',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-populate numeric_value and text_value from value
        static::saving(function ($attributeValue) {
            $value = $attributeValue->value;
            
            // Extract numeric value if applicable
            if (is_numeric($value)) {
                $attributeValue->numeric_value = (float) $value;
            } elseif (is_array($value) && isset($value['value']) && is_numeric($value['value'])) {
                $attributeValue->numeric_value = (float) $value['value'];
            } else {
                $attributeValue->numeric_value = null;
            }
            
            // Extract text value for searchability
            if (is_string($value)) {
                $attributeValue->text_value = $value;
            } elseif (is_array($value)) {
                // For translatable values, use current locale or first available
                $locale = app()->getLocale();
                $attributeValue->text_value = $value[$locale] ?? $value[array_key_first($value)] ?? json_encode($value);
            } else {
                $attributeValue->text_value = json_encode($value);
            }
        });
    }

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
     * Channel relationship.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Attribute relationship.
     *
     * @return BelongsTo
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Get the display value for the attribute.
     *
     * @param  string|null  $locale
     * @return mixed
     */
    public function getDisplayValue(?string $locale = null)
    {
        $value = $this->value;
        $attribute = $this->attribute;
        
        if (!$attribute) {
            return $value;
        }

        // Handle different attribute types
        $type = class_basename($attribute->type);
        
        switch ($type) {
            case 'Number':
                return $this->numeric_value . ($attribute->unit ? ' ' . $attribute->unit : '');
            
            case 'TranslatedText':
                $locale = $locale ?? app()->getLocale();
                if (is_array($value)) {
                    return $value[$locale] ?? $value[array_key_first($value)] ?? '';
                }
                return $value;
            
            case 'Boolean':
                return (bool) $value ? 'Yes' : 'No';
            
            case 'Color':
                return is_array($value) ? ($value['hex'] ?? $value['name'] ?? json_encode($value)) : $value;
            
            case 'Date':
                return is_string($value) ? $value : (isset($value['date']) ? $value['date'] : json_encode($value));
            
            default:
                return is_array($value) ? json_encode($value) : $value;
        }
    }
}

