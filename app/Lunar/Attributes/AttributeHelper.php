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
}


