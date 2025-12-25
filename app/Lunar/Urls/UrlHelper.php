<?php

namespace App\Lunar\Urls;

use Illuminate\Database\Eloquent\Model;
use Lunar\Models\Language;
use Lunar\Models\Url;

/**
 * Helper class for working with Lunar URLs.
 * 
 * Provides convenience methods for creating, managing, and retrieving URLs.
 * See: https://docs.lunarphp.com/1.x/reference/urls
 */
class UrlHelper
{
    /**
     * Create a URL for a model.
     * 
     * @param Model $model The model instance (must use HasUrls trait)
     * @param string $slug The slug for the URL
     * @param Language|int|null $language Language instance or ID (defaults to default language)
     * @param bool $default Whether this is the default URL for the language
     * @return Url
     */
    public static function create(Model $model, string $slug, Language|int|null $language = null, bool $default = true): Url
    {
        if (!$language) {
            $language = Language::where('default', true)->first();
        }

        if ($language instanceof Language) {
            $languageId = $language->id;
        } else {
            $languageId = $language;
        }

        return Url::create([
            'slug' => $slug,
            'language_id' => $languageId,
            'element_type' => $model->getMorphClass(),
            'element_id' => $model->id,
            'default' => $default,
        ]);
    }

    /**
     * Get the default URL for a model in a specific language.
     * 
     * @param Model $model
     * @param Language|int|null $language Language instance or ID (defaults to default language)
     * @return Url|null
     */
    public static function getDefaultUrl(Model $model, Language|int|null $language = null): ?Url
    {
        if (!$language) {
            $language = Language::where('default', true)->first();
        }

        if ($language instanceof Language) {
            $languageId = $language->id;
        } else {
            $languageId = $language;
        }

        return Url::where('element_type', $model->getMorphClass())
            ->where('element_id', $model->id)
            ->where('language_id', $languageId)
            ->where('default', true)
            ->first();
    }

    /**
     * Get the default slug for a model.
     * 
     * Convenience method to get the slug from the default URL.
     * 
     * @param Model $model
     * @param Language|int|null $language Language instance or ID (defaults to default language)
     * @return string|null
     */
    public static function getDefaultSlug(Model $model, Language|int|null $language = null): ?string
    {
        $url = static::getDefaultUrl($model, $language);
        return $url?->slug;
    }

    /**
     * Get all URLs for a model.
     * 
     * @param Model $model
     * @return \Illuminate\Support\Collection
     */
    public static function getUrls(Model $model): \Illuminate\Support\Collection
    {
        return Url::where('element_type', $model->getMorphClass())
            ->where('element_id', $model->id)
            ->get();
    }

    /**
     * Update or create a default URL for a model.
     * 
     * If a default URL exists for the language, it will be updated.
     * Otherwise, a new one will be created.
     * 
     * @param Model $model
     * @param string $slug
     * @param Language|int|null $language Language instance or ID (defaults to default language)
     * @return Url
     */
    public static function updateOrCreateDefault(Model $model, string $slug, Language|int|null $language = null): Url
    {
        if (!$language) {
            $language = Language::where('default', true)->first();
        }

        if ($language instanceof Language) {
            $languageId = $language->id;
        } else {
            $languageId = $language;
        }

        return Url::updateOrCreate(
            [
                'element_type' => $model->getMorphClass(),
                'element_id' => $model->id,
                'language_id' => $languageId,
                'default' => true,
            ],
            [
                'slug' => $slug,
            ]
        );
    }

    /**
     * Delete a URL by slug and model.
     * 
     * If the deleted URL was the default, Lunar will automatically
     * promote another URL to default if available.
     * 
     * @param Model $model
     * @param string $slug
     * @param Language|int|null $language Language instance or ID (optional)
     * @return bool
     */
    public static function deleteBySlug(Model $model, string $slug, Language|int|null $language = null): bool
    {
        $query = Url::where('element_type', $model->getMorphClass())
            ->where('element_id', $model->id)
            ->where('slug', $slug);

        if ($language) {
            if ($language instanceof Language) {
                $languageId = $language->id;
            } else {
                $languageId = $language;
            }
            $query->where('language_id', $languageId);
        }

        $url = $query->first();

        if ($url) {
            return $url->delete();
        }

        return false;
    }

    /**
     * Check if a slug is available for a language.
     * 
     * @param string $slug
     * @param Language|int $language Language instance or ID
     * @param Model|null $excludeModel Model to exclude from the check (e.g., when updating)
     * @return bool True if available, false if taken
     */
    public static function isSlugAvailable(string $slug, Language|int $language, ?Model $excludeModel = null): bool
    {
        if ($language instanceof Language) {
            $languageId = $language->id;
        } else {
            $languageId = $language;
        }

        $query = Url::where('slug', $slug)
            ->where('language_id', $languageId);

        if ($excludeModel) {
            $query->where(function ($q) use ($excludeModel) {
                $q->where('element_type', '!=', $excludeModel->getMorphClass())
                    ->orWhere('element_id', '!=', $excludeModel->id);
            });
        }

        return !$query->exists();
    }
}


