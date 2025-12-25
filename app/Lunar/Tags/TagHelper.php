<?php

namespace App\Lunar\Tags;

use Illuminate\Support\Collection;
use Lunar\Models\Tag;

/**
 * Helper class for working with Lunar Tags.
 * 
 * Provides convenience methods for managing tags and tag attachments.
 * See: https://docs.lunarphp.com/1.x/reference/tags
 */
class TagHelper
{
    /**
     * Get all tags.
     * 
     * @return Collection<Tag>
     */
    public static function getAll(): Collection
    {
        return Tag::all();
    }

    /**
     * Find a tag by ID.
     * 
     * @param int $id
     * @return Tag|null
     */
    public static function find(int $id): ?Tag
    {
        return Tag::find($id);
    }

    /**
     * Find a tag by name.
     * 
     * Note: Tags are stored in uppercase, so this will search for the uppercase version.
     * 
     * @param string $name Tag name (will be converted to uppercase for search)
     * @return Tag|null
     */
    public static function findByName(string $name): ?Tag
    {
        return Tag::where('value', strtoupper($name))->first();
    }

    /**
     * Find or create a tag by name.
     * 
     * Note: Tags are converted to uppercase when saved.
     * 
     * @param string $name Tag name (will be converted to uppercase)
     * @return Tag
     */
    public static function findOrCreate(string $name): Tag
    {
        $uppercaseName = strtoupper($name);
        return Tag::firstOrCreate(['value' => $uppercaseName]);
    }

    /**
     * Create multiple tags from an array of names.
     * 
     * Tags are converted to uppercase when saved.
     * 
     * @param array|Collection $names Array or Collection of tag names
     * @return Collection<Tag>
     */
    public static function createMany(array|Collection $names): Collection
    {
        if (is_array($names)) {
            $names = collect($names);
        }

        return $names->map(function ($name) {
            return static::findOrCreate($name);
        });
    }

    /**
     * Sync tags on a model.
     * 
     * The model must use the HasTags trait.
     * Note: This runs via a job, so it will process in the background if queues are set up.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @param array|Collection|string $tags Tags to sync (can be array, Collection, or single string)
     * @return void
     */
    public static function syncTags($model, array|Collection|string $tags): void
    {
        // Convert single string to array
        if (is_string($tags)) {
            $tags = [$tags];
        }

        // Convert array to collection
        if (is_array($tags)) {
            $tags = collect($tags);
        }

        $model->syncTags($tags);
    }

    /**
     * Add tags to a model without removing existing tags.
     * 
     * The model must use the HasTags trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @param array|Collection|string $tags Tags to add
     * @return void
     */
    public static function addTags($model, array|Collection|string $tags): void
    {
        // Convert single string to array
        if (is_string($tags)) {
            $tags = [$tags];
        }

        // Convert array to collection
        if (is_array($tags)) {
            $tags = collect($tags);
        }

        $currentTags = $model->tags->pluck('value')->map('strtolower');
        $newTags = $tags->reject(function ($tag) use ($currentTags) {
            return $currentTags->contains(strtolower($tag));
        });

        if ($newTags->isNotEmpty()) {
            $allTags = $currentTags->merge($newTags);
            $model->syncTags($allTags);
        }
    }

    /**
     * Remove tags from a model.
     * 
     * The model must use the HasTags trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @param array|Collection|string $tags Tags to remove
     * @return void
     */
    public static function removeTags($model, array|Collection|string $tags): void
    {
        // Convert single string to array
        if (is_string($tags)) {
            $tags = [$tags];
        }

        // Convert array to collection
        if (is_array($tags)) {
            $tags = collect($tags);
        }

        $currentTags = $model->tags->pluck('value')->map('strtolower');
        $remainingTags = $currentTags->reject(function ($tag) use ($tags) {
            return $tags->map('strtolower')->contains(strtolower($tag));
        });

        $model->syncTags($remainingTags);
    }

    /**
     * Get tags for a model.
     * 
     * The model must use the HasTags trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @return Collection<Tag>
     */
    public static function getTagsForModel($model): Collection
    {
        return $model->tags;
    }

    /**
     * Get tag names (values) for a model.
     * 
     * The model must use the HasTags trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @return Collection<string>
     */
    public static function getTagNamesForModel($model): Collection
    {
        return $model->tags->pluck('value');
    }

    /**
     * Check if a model has a specific tag.
     * 
     * The model must use the HasTags trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @param string $tagName Tag name (case-insensitive comparison)
     * @return bool
     */
    public static function hasTag($model, string $tagName): bool
    {
        return $model->tags->contains(function ($tag) use ($tagName) {
            return strtoupper($tag->value) === strtoupper($tagName);
        });
    }

    /**
     * Clear all tags from a model.
     * 
     * The model must use the HasTags trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasTags trait
     * @return void
     */
    public static function clearTags($model): void
    {
        $model->syncTags([]);
    }
}


