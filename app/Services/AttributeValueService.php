<?php

namespace App\Services;

use App\Models\ProductAttributeValue;
use App\Models\VariantAttributeValue;
use App\Models\ChannelAttributeValue;
use App\Models\AttributeValueHistory;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariant;
use Lunar\Models\Channel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Attribute Value Service.
 * 
 * Manages:
 * - Typed storage
 * - Per-locale values
 * - Per-channel values
 * - Variant overrides
 * - Fallback logic
 * - Change history
 */
class AttributeValueService
{
    /**
     * Set product attribute value.
     *
     * @param  Product  $product
     * @param  Attribute|int|string  $attribute
     * @param  mixed  $value
     * @param  string|null  $locale
     * @param  bool  $isOverride
     * @return ProductAttributeValue
     */
    public function setProductValue(
        Product $product,
        Attribute|int|string $attribute,
        $value,
        ?string $locale = null,
        bool $isOverride = false
    ): ProductAttributeValue {
        $attribute = $this->resolveAttribute($attribute);
        $locale = $locale ?? app()->getLocale();
        $userId = Auth::id();

        return DB::transaction(function () use ($product, $attribute, $value, $locale, $isOverride, $userId) {
            // Get existing value
            $existing = ProductAttributeValue::where('product_id', $product->id)
                ->where('attribute_id', $attribute->id)
                ->where('locale', $locale)
                ->first();

            $valueBefore = $existing ? $existing->value : null;
            $numericBefore = $existing ? $existing->numeric_value : null;
            $textBefore = $existing ? $existing->text_value : null;

            // Prepare typed value
            $typedValue = $this->prepareTypedValue($attribute, $value, $locale);

            // Create or update
            if ($existing) {
                $existing->update([
                    'value' => $typedValue,
                    'is_override' => $isOverride,
                    'updated_by' => $userId,
                ]);
                $valueRecord = $existing->fresh();
                $changeType = 'updated';
            } else {
                $valueRecord = ProductAttributeValue::create([
                    'product_id' => $product->id,
                    'attribute_id' => $attribute->id,
                    'locale' => $locale,
                    'value' => $typedValue,
                    'is_override' => $isOverride,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $changeType = 'created';
            }

            // Log history
            $this->logHistory(
                valueable: $valueRecord,
                attribute: $attribute,
                valueBefore: $valueBefore,
                valueAfter: $typedValue,
                numericBefore: $numericBefore,
                numericAfter: $valueRecord->numeric_value,
                textBefore: $textBefore,
                textAfter: $valueRecord->text_value,
                changeType: $changeType,
                locale: $locale,
                userId: $userId
            );

            return $valueRecord;
        });
    }

    /**
     * Set variant attribute value.
     *
     * @param  ProductVariant  $variant
     * @param  Attribute|int|string  $attribute
     * @param  mixed  $value
     * @param  string|null  $locale
     * @param  bool  $isOverride
     * @return VariantAttributeValue
     */
    public function setVariantValue(
        ProductVariant $variant,
        Attribute|int|string $attribute,
        $value,
        ?string $locale = null,
        bool $isOverride = true
    ): VariantAttributeValue {
        $attribute = $this->resolveAttribute($attribute);
        $locale = $locale ?? app()->getLocale();
        $userId = Auth::id();

        return DB::transaction(function () use ($variant, $attribute, $value, $locale, $isOverride, $userId) {
            // Get existing value
            $existing = VariantAttributeValue::where('product_variant_id', $variant->id)
                ->where('attribute_id', $attribute->id)
                ->where('locale', $locale)
                ->first();

            $valueBefore = $existing ? $existing->value : null;
            $numericBefore = $existing ? $existing->numeric_value : null;
            $textBefore = $existing ? $existing->text_value : null;

            // Prepare typed value
            $typedValue = $this->prepareTypedValue($attribute, $value, $locale);

            // Create or update
            if ($existing) {
                $existing->update([
                    'value' => $typedValue,
                    'is_override' => $isOverride,
                    'updated_by' => $userId,
                ]);
                $valueRecord = $existing->fresh();
                $changeType = 'updated';
            } else {
                $valueRecord = VariantAttributeValue::create([
                    'product_variant_id' => $variant->id,
                    'attribute_id' => $attribute->id,
                    'locale' => $locale,
                    'value' => $typedValue,
                    'is_override' => $isOverride,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $changeType = 'created';
            }

            // Log history
            $this->logHistory(
                valueable: $valueRecord,
                attribute: $attribute,
                valueBefore: $valueBefore,
                valueAfter: $typedValue,
                numericBefore: $numericBefore,
                numericAfter: $valueRecord->numeric_value,
                textBefore: $textBefore,
                textAfter: $valueRecord->text_value,
                changeType: $changeType,
                locale: $locale,
                userId: $userId
            );

            return $valueRecord;
        });
    }

    /**
     * Set channel attribute value.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  Attribute|int|string  $attribute
     * @param  mixed  $value
     * @param  string|null  $locale
     * @param  bool  $isOverride
     * @return ChannelAttributeValue
     */
    public function setChannelValue(
        Product $product,
        Channel $channel,
        Attribute|int|string $attribute,
        $value,
        ?string $locale = null,
        bool $isOverride = false
    ): ChannelAttributeValue {
        $attribute = $this->resolveAttribute($attribute);
        $locale = $locale ?? app()->getLocale();
        $userId = Auth::id();

        return DB::transaction(function () use ($product, $channel, $attribute, $value, $locale, $isOverride, $userId) {
            // Get existing value
            $existing = ChannelAttributeValue::where('product_id', $product->id)
                ->where('channel_id', $channel->id)
                ->where('attribute_id', $attribute->id)
                ->where('locale', $locale)
                ->first();

            $valueBefore = $existing ? $existing->value : null;
            $numericBefore = $existing ? $existing->numeric_value : null;
            $textBefore = $existing ? $existing->text_value : null;

            // Prepare typed value
            $typedValue = $this->prepareTypedValue($attribute, $value, $locale);

            // Create or update
            if ($existing) {
                $existing->update([
                    'value' => $typedValue,
                    'is_override' => $isOverride,
                    'updated_by' => $userId,
                ]);
                $valueRecord = $existing->fresh();
                $changeType = 'updated';
            } else {
                $valueRecord = ChannelAttributeValue::create([
                    'product_id' => $product->id,
                    'channel_id' => $channel->id,
                    'attribute_id' => $attribute->id,
                    'locale' => $locale,
                    'value' => $typedValue,
                    'is_override' => $isOverride,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $changeType = 'created';
            }

            // Log history
            $this->logHistory(
                valueable: $valueRecord,
                attribute: $attribute,
                valueBefore: $valueBefore,
                valueAfter: $typedValue,
                numericBefore: $numericBefore,
                numericAfter: $valueRecord->numeric_value,
                textBefore: $textBefore,
                textAfter: $valueRecord->text_value,
                changeType: $changeType,
                locale: $locale,
                userId: $userId
            );

            return $valueRecord;
        });
    }

    /**
     * Get attribute value with fallback logic.
     *
     * Priority order:
     * 1. Variant value (if variant provided)
     * 2. Channel value (if channel provided)
     * 3. Product value
     * 4. Default value from attribute definition
     *
     * @param  Product  $product
     * @param  Attribute|int|string  $attribute
     * @param  ProductVariant|null  $variant
     * @param  Channel|null  $channel
     * @param  string|null  $locale
     * @return mixed
     */
    public function getValue(
        Product $product,
        Attribute|int|string $attribute,
        ?ProductVariant $variant = null,
        ?Channel $channel = null,
        ?string $locale = null
    ) {
        $attribute = $this->resolveAttribute($attribute);
        $locale = $locale ?? app()->getLocale();

        // 1. Try variant value
        if ($variant) {
            $variantValue = VariantAttributeValue::where('product_variant_id', $variant->id)
                ->where('attribute_id', $attribute->id)
                ->where('locale', $locale)
                ->first();

            if ($variantValue) {
                return $this->formatValue($attribute, $variantValue->value, $locale);
            }
        }

        // 2. Try channel value
        if ($channel) {
            $channelValue = ChannelAttributeValue::where('product_id', $product->id)
                ->where('channel_id', $channel->id)
                ->where('attribute_id', $attribute->id)
                ->where('locale', $locale)
                ->first();

            if ($channelValue) {
                return $this->formatValue($attribute, $channelValue->value, $locale);
            }
        }

        // 3. Try product value
        $productValue = ProductAttributeValue::where('product_id', $product->id)
            ->where('attribute_id', $attribute->id)
            ->where('locale', $locale)
            ->first();

        if ($productValue) {
            return $this->formatValue($attribute, $productValue->value, $locale);
        }

        // 4. Fallback to default value
        $defaultValue = $attribute->getDefaultValue();
        if ($defaultValue !== null) {
            return $this->formatValue($attribute, $defaultValue, $locale);
        }

        return null;
    }

    /**
     * Get change history for an attribute value.
     *
     * @param  mixed  $valueable
     * @param  Attribute|int|string  $attribute
     * @param  string|null  $locale
     * @return \Illuminate\Support\Collection
     */
    public function getHistory($valueable, Attribute|int|string $attribute, ?string $locale = null)
    {
        $attribute = $this->resolveAttribute($attribute);

        $query = AttributeValueHistory::where('valueable_type', get_class($valueable))
            ->where('valueable_id', $valueable->id)
            ->where('attribute_id', $attribute->id);

        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Prepare typed value based on attribute type.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @param  string  $locale
     * @return mixed
     */
    protected function prepareTypedValue(Attribute $attribute, $value, string $locale)
    {
        $type = class_basename($attribute->type);

        return match($type) {
            'TranslatedText', 'Text' => $attribute->isLocalizable()
                ? [$locale => $value]
                : $value,
            'Number' => is_numeric($value) ? (float) $value : $value,
            'Boolean' => (bool) $value,
            'Date', 'DateTime' => is_string($value) ? $value : ($value instanceof \DateTime ? $value->format('Y-m-d') : $value),
            'JSON', 'Json' => is_array($value) || is_object($value) ? $value : json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Format value for display based on attribute type.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @param  string  $locale
     * @return mixed
     */
    protected function formatValue(Attribute $attribute, $value, string $locale)
    {
        if ($value === null) {
            return null;
        }

        $type = class_basename($attribute->type);

        // Handle localized values
        if (is_array($value) && $attribute->isLocalizable()) {
            return $value[$locale] ?? $value[array_key_first($value)] ?? null;
        }

        return $value;
    }

    /**
     * Resolve attribute from ID, handle, or Attribute instance.
     *
     * @param  Attribute|int|string  $attribute
     * @return Attribute
     */
    protected function resolveAttribute(Attribute|int|string $attribute): Attribute
    {
        if ($attribute instanceof Attribute) {
            return $attribute;
        }

        if (is_numeric($attribute)) {
            return Attribute::findOrFail($attribute);
        }

        return Attribute::where('handle', $attribute)
            ->orWhere('code', $attribute)
            ->firstOrFail();
    }

    /**
     * Log attribute value change history.
     *
     * @param  mixed  $valueable
     * @param  Attribute  $attribute
     * @param  mixed  $valueBefore
     * @param  mixed  $valueAfter
     * @param  float|null  $numericBefore
     * @param  float|null  $numericAfter
     * @param  string|null  $textBefore
     * @param  string|null  $textAfter
     * @param  string  $changeType
     * @param  string  $locale
     * @param  int|null  $userId
     * @return void
     */
    protected function logHistory(
        $valueable,
        Attribute $attribute,
        $valueBefore,
        $valueAfter,
        ?float $numericBefore,
        ?float $numericAfter,
        ?string $textBefore,
        ?string $textAfter,
        string $changeType,
        string $locale,
        ?int $userId
    ): void {
        AttributeValueHistory::create([
            'valueable_type' => get_class($valueable),
            'valueable_id' => $valueable->id,
            'attribute_id' => $attribute->id,
            'value_before' => $valueBefore,
            'value_after' => $valueAfter,
            'numeric_value_before' => $numericBefore,
            'numeric_value_after' => $numericAfter,
            'text_value_before' => $textBefore,
            'text_value_after' => $textAfter,
            'change_type' => $changeType,
            'locale' => $locale,
            'changed_by' => $userId,
        ]);
    }
}


