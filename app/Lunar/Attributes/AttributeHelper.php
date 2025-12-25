<?php

namespace App\Lunar\Attributes;

use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Product;

/**
 * Helper class for working with Lunar attributes.
 * 
 * Provides convenience methods for creating and accessing attribute data.
 * See: https://docs.lunarphp.com/1.x/reference/attributes
 */
class AttributeHelper
{
    /**
     * Create a TranslatedText attribute value.
     * 
     * @param array $translations Array of language => value pairs
     * @return TranslatedText
     */
    public static function translatedText(array $translations): TranslatedText
    {
        $translated = collect();
        foreach ($translations as $lang => $value) {
            $translated[$lang] = new Text($value);
        }
        
        return new TranslatedText($translated);
    }

    /**
     * Create a Text attribute value.
     * 
     * @param string $value
     * @return Text
     */
    public static function text(string $value): Text
    {
        return new Text($value);
    }

    /**
     * Create a Number attribute value.
     * 
     * @param int|float $value
     * @return Number
     */
    public static function number(int|float $value): Number
    {
        return new Number($value);
    }

    /**
     * Get an attribute value from a product.
     * 
     * Example:
     * AttributeHelper::get($product, 'name'); // Returns translated text value
     * AttributeHelper::get($product, 'weight'); // Returns number value
     * 
     * @param Product $product
     * @param string $handle The attribute handle
     * @param string|null $locale Optional locale for translated attributes
     * @return mixed The attribute value
     */
    public static function get(Product $product, string $handle, ?string $locale = null): mixed
    {
        return $product->translateAttribute($handle, $locale);
    }

    /**
     * Check if a product has a specific attribute.
     * 
     * @param Product $product
     * @param string $handle
     * @return bool
     */
    public static function has(Product $product, string $handle): bool
    {
        return $product->attribute_data->has($handle);
    }

    /**
     * Get all attribute values for a product as an array.
     * 
     * @param Product $product
     * @param string|null $locale Optional locale for translated attributes
     * @return array
     */
    public static function all(Product $product, ?string $locale = null): array
    {
        return $product->attribute_data->mapWithKeys(function ($value, $key) use ($locale) {
            if ($value instanceof TranslatedText) {
                return [$key => $value->getValue()[$locale ?? 'en']?->getValue() ?? $value->getValue()->first()?->getValue()];
            }
            
            return [$key => $value->getValue() ?? $value];
        })->toArray();
    }

    /**
     * Get an attribute value with fallback support.
     * 
     * Tries the requested locale first, then falls back to default language,
     * then to the first available translation.
     * 
     * @param Product $product
     * @param string $handle The attribute handle
     * @param string|null $locale Optional locale (defaults to current locale)
     * @param string|null $fallbackLocale Optional fallback locale (defaults to default language)
     * @return mixed The attribute value
     */
    public static function getWithFallback(Product $product, string $handle, ?string $locale = null, ?string $fallbackLocale = null): mixed
    {
        // Use current locale if not specified
        if ($locale === null) {
            $locale = app()->getLocale();
        }

        // Get default language if fallback not specified
        if ($fallbackLocale === null) {
            $defaultLanguage = \Lunar\Models\Language::getDefault();
            $fallbackLocale = $defaultLanguage ? $defaultLanguage->code : 'en';
        }

        // Try requested locale first
        $value = $product->translateAttribute($handle, $locale);
        if ($value !== null) {
            return $value;
        }

        // Fallback to default language
        if ($locale !== $fallbackLocale) {
            $value = $product->translateAttribute($handle, $fallbackLocale);
            if ($value !== null) {
                return $value;
            }
        }

        // Last resort: use translateAttribute without locale (uses first available)
        return $product->translateAttribute($handle);
    }

    /**
     * Create a Boolean attribute value.
     * 
     * @param bool $value
     * @return bool
     */
    public static function boolean(bool $value): bool
    {
        return $value;
    }

    /**
     * Create a Color attribute value.
     * 
     * @param string $hex Hex color code (e.g., "#FF0000")
     * @param string|null $name Optional color name
     * @return array
     */
    public static function color(string $hex, ?string $name = null): array
    {
        return [
            'hex' => $hex,
            'name' => $name,
        ];
    }

    /**
     * Create a Date attribute value.
     * 
     * @param string|\DateTime $date Date string or DateTime object
     * @return string
     */
    public static function date(string|\DateTime $date): string
    {
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }
        return $date;
    }

    /**
     * Create a Measurement attribute value with unit.
     * 
     * @param float $value The numeric value
     * @param string $unit The unit (e.g., "kg", "cm", "inches")
     * @return array
     */
    public static function measurement(float $value, string $unit): array
    {
        return [
            'value' => $value,
            'unit' => $unit,
        ];
    }

    /**
     * Create a JSON attribute value.
     * 
     * @param array $data Any array data
     * @return array
     */
    public static function json(array $data): array
    {
        return $data;
    }
}


