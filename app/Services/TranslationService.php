<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\Collection;
use Lunar\FieldTypes\TranslatedText;
use Lunar\FieldTypes\Text;
use App\Lunar\Attributes\AttributeHelper;

/**
 * Service for handling translations across the application.
 * 
 * Provides utilities for working with multilingual content,
 * fallback handling, and translation management.
 */
class TranslationService
{
    /**
     * Get translated attribute with fallback support.
     * 
     * Tries the current locale first, then falls back to default language,
     * then to the first available translation.
     * 
     * @param Model $model The model instance (Product, Collection, etc.)
     * @param string $attributeHandle The attribute handle (e.g., 'name', 'description')
     * @param string|null $locale Optional locale (defaults to current locale)
     * @param string|null $fallbackLocale Optional fallback locale (defaults to default language)
     * @return mixed The translated value or null if not found
     */
    public static function translate(Model $model, string $attributeHandle, ?string $locale = null, ?string $fallbackLocale = null): mixed
    {
        // Use current locale if not specified
        if ($locale === null) {
            $locale = app()->getLocale();
        }

        // Get default language if fallback not specified
        if ($fallbackLocale === null) {
            $defaultLanguage = Language::getDefault();
            $fallbackLocale = $defaultLanguage ? $defaultLanguage->code : 'en';
        }

        // Try requested locale first
        $value = $model->translateAttribute($attributeHandle, $locale);
        if ($value !== null && $value !== '') {
            return $value;
        }

        // Fallback to default language
        if ($locale !== $fallbackLocale) {
            $value = $model->translateAttribute($attributeHandle, $fallbackLocale);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        // Last resort: get first available translation
        return $model->translateAttribute($attributeHandle);
    }

    /**
     * Check if a translation exists for a specific locale.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @param string $locale The locale to check
     * @return bool True if translation exists
     */
    public static function hasTranslation(Model $model, string $attributeHandle, string $locale): bool
    {
        $value = $model->translateAttribute($attributeHandle, $locale);
        return $value !== null && $value !== '';
    }

    /**
     * Get all available locales for a specific attribute.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @return array Array of locale codes that have translations
     */
    public static function getAvailableLocales(Model $model, string $attributeHandle): array
    {
        $attributeData = $model->attribute_data->get($attributeHandle);
        
        if (!$attributeData instanceof TranslatedText) {
            return [];
        }

        $translations = $attributeData->getValue();
        return $translations->keys()->toArray();
    }

    /**
     * Get the translation with fallback information.
     * 
     * Returns an array with the value, locale used, and whether it was a fallback.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @param string|null $locale Optional locale (defaults to current locale)
     * @param string|null $fallbackLocale Optional fallback locale
     * @return array Array with 'value', 'locale', and 'is_fallback' keys
     */
    public static function translateWithInfo(Model $model, string $attributeHandle, ?string $locale = null, ?string $fallbackLocale = null): array
    {
        // Use current locale if not specified
        if ($locale === null) {
            $locale = app()->getLocale();
        }

        // Get default language if fallback not specified
        if ($fallbackLocale === null) {
            $defaultLanguage = Language::getDefault();
            $fallbackLocale = $defaultLanguage ? $defaultLanguage->code : 'en';
        }

        // Try requested locale first
        $value = $model->translateAttribute($attributeHandle, $locale);
        if ($value !== null && $value !== '') {
            return [
                'value' => $value,
                'locale' => $locale,
                'is_fallback' => false,
            ];
        }

        // Fallback to default language
        if ($locale !== $fallbackLocale) {
            $value = $model->translateAttribute($attributeHandle, $fallbackLocale);
            if ($value !== null && $value !== '') {
                return [
                    'value' => $value,
                    'locale' => $fallbackLocale,
                    'is_fallback' => true,
                ];
            }
        }

        // Last resort: get first available
        $firstAvailable = $model->translateAttribute($attributeHandle);
        $availableLocales = static::getAvailableLocales($model, $attributeHandle);
        $firstLocale = $availableLocales[0] ?? $fallbackLocale;

        return [
            'value' => $firstAvailable,
            'locale' => $firstLocale,
            'is_fallback' => $locale !== $firstLocale,
        ];
    }

    /**
     * Create a TranslatedText attribute from an array of translations.
     * 
     * @param array $translations Array of locale => value pairs
     * @return TranslatedText
     */
    public static function createTranslatedText(array $translations): TranslatedText
    {
        return AttributeHelper::translatedText($translations);
    }

    /**
     * Add or update a translation for a specific locale.
     * 
     * Note: This requires updating the model's attribute_data directly.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @param string $locale The locale code
     * @param string $value The translation value
     * @return void
     */
    public static function setTranslation(Model $model, string $attributeHandle, string $locale, string $value): void
    {
        $attributeData = $model->attribute_data;
        $currentAttribute = $attributeData->get($attributeHandle);

        if ($currentAttribute instanceof TranslatedText) {
            $translations = $currentAttribute->getValue();
            $translations[$locale] = new Text($value);
            $attributeData[$attributeHandle] = new TranslatedText($translations);
        } else {
            // Create new TranslatedText
            $translations = collect([$locale => new Text($value)]);
            $attributeData[$attributeHandle] = new TranslatedText($translations);
        }

        $model->attribute_data = $attributeData;
        $model->save();
    }

    /**
     * Get all translations for an attribute as an array.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @return array Array of locale => value pairs
     */
    public static function getAllTranslations(Model $model, string $attributeHandle): array
    {
        $attributeData = $model->attribute_data->get($attributeHandle);
        
        if (!$attributeData instanceof TranslatedText) {
            return [];
        }

        $translations = [];
        foreach ($attributeData->getValue() as $locale => $textField) {
            $translations[$locale] = $textField->getValue();
        }

        return $translations;
    }

    /**
     * Check if all required locales have translations.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @param array $requiredLocales Array of required locale codes
     * @return bool True if all required locales have translations
     */
    public static function hasAllTranslations(Model $model, string $attributeHandle, array $requiredLocales): bool
    {
        $availableLocales = static::getAvailableLocales($model, $attributeHandle);
        
        foreach ($requiredLocales as $locale) {
            if (!in_array($locale, $availableLocales)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing translations for an attribute.
     * 
     * @param Model $model The model instance
     * @param string $attributeHandle The attribute handle
     * @param array $requiredLocales Array of required locale codes
     * @return array Array of locale codes that are missing translations
     */
    public static function getMissingTranslations(Model $model, string $attributeHandle, array $requiredLocales): array
    {
        $availableLocales = static::getAvailableLocales($model, $attributeHandle);
        return array_diff($requiredLocales, $availableLocales);
    }
}

