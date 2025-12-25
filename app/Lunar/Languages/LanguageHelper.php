<?php

namespace App\Lunar\Languages;

use Illuminate\Support\Collection;
use Lunar\Models\Language;

/**
 * Helper class for working with Lunar Languages.
 * 
 * Provides convenience methods for managing languages.
 * See: https://docs.lunarphp.com/1.x/reference/languages
 */
class LanguageHelper
{
    /**
     * Get the default language.
     * 
     * @return Language|null
     */
    public static function getDefault(): ?Language
    {
        return Language::getDefault();
    }

    /**
     * Get all languages.
     * 
     * @return Collection<Language>
     */
    public static function getAll(): Collection
    {
        return Language::all();
    }

    /**
     * Find a language by ID.
     * 
     * @param int $id
     * @return Language|null
     */
    public static function find(int $id): ?Language
    {
        return Language::find($id);
    }

    /**
     * Find a language by code (typically ISO 2 character code).
     * 
     * @param string $code Language code (e.g., 'en', 'fr', 'de')
     * @return Language|null
     */
    public static function findByCode(string $code): ?Language
    {
        return Language::where('code', $code)->first();
    }

    /**
     * Create a new language.
     * 
     * @param string $code Language code (typically ISO 2 character code, e.g., 'en', 'fr')
     * @param string $name Descriptive name (e.g., 'English', 'French')
     * @param bool $default Whether this is the default language (only one should be default)
     * @return Language
     */
    public static function create(
        string $code,
        string $name,
        bool $default = false
    ): Language {
        // If setting as default, unset any existing default first
        if ($default) {
            Language::where('default', true)->update(['default' => false]);
        }

        return Language::create([
            'code' => $code,
            'name' => $name,
            'default' => $default,
        ]);
    }

    /**
     * Set a language as the default.
     * 
     * This will automatically unset any existing default language.
     * There should only ever be one default language.
     * 
     * @param Language|string $language Language instance or code
     * @return Language
     */
    public static function setDefault(Language|string $language): Language
    {
        $languageCode = is_string($language) ? $language : $language->code;
        
        if (is_string($language)) {
            $language = static::findByCode($language);
            if (!$language) {
                throw new \InvalidArgumentException("Language with code '{$languageCode}' not found");
            }
        }

        // Unset existing default
        Language::where('default', true)->update(['default' => false]);

        // Set new default
        $language->update(['default' => true]);
        
        return $language->fresh();
    }

    /**
     * Check if a language is the default.
     * 
     * @param Language $language
     * @return bool
     */
    public static function isDefault(Language $language): bool
    {
        return $language->default;
    }

    /**
     * Get enabled languages (all languages are typically enabled by default).
     * 
     * Note: Languages don't have an 'enabled' field in the base schema,
     * but this method is provided for consistency and future extensibility.
     * 
     * @return Collection<Language>
     */
    public static function getEnabled(): Collection
    {
        // Languages don't have an enabled field by default
        // Return all languages for now
        return static::getAll();
    }

    /**
     * Check if a language code exists.
     * 
     * @param string $code Language code
     * @return bool
     */
    public static function exists(string $code): bool
    {
        return static::findByCode($code) !== null;
    }
}


