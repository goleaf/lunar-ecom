<?php

namespace App\Services;

use App\Models\Category;
use Lunar\Models\Channel;
use Lunar\Models\Language;
use Illuminate\Support\Collection;

/**
 * Service for managing category visibility per channel and locale.
 */
class CategoryVisibilityService
{
    /**
     * Set category visibility for a channel.
     *
     * @param  Category  $category
     * @param  Channel|int  $channel
     * @param  bool  $isVisible
     * @param  bool|null  $isInNavigation  Null to keep current value
     * @return void
     */
    public function setChannelVisibility(
        Category $category,
        $channel,
        bool $isVisible,
        ?bool $isInNavigation = null
    ): void {
        $channelId = $channel instanceof Channel ? $channel->id : $channel;
        
        $pivotData = ['is_visible' => $isVisible];
        
        if ($isInNavigation !== null) {
            $pivotData['is_in_navigation'] = $isInNavigation;
        } else {
            // Get current value if exists, otherwise use global setting
            $existing = $category->channels()->where('channel_id', $channelId)->first();
            $pivotData['is_in_navigation'] = $existing 
                ? $existing->pivot->is_in_navigation 
                : $category->show_in_navigation;
        }
        
        $category->channels()->syncWithoutDetaching([
            $channelId => $pivotData
        ]);
        
        // Clear cache
        $this->clearCategoryCache($category);
    }

    /**
     * Set category visibility for a language/locale.
     *
     * @param  Category  $category
     * @param  Language|int|string  $language
     * @param  bool  $isVisible
     * @param  bool|null  $isInNavigation  Null to keep current value
     * @return void
     */
    public function setLanguageVisibility(
        Category $category,
        $language,
        bool $isVisible,
        ?bool $isInNavigation = null
    ): void {
        $languageId = $this->resolveLanguageId($language);
        
        if (!$languageId) {
            return;
        }
        
        $pivotData = ['is_visible' => $isVisible];
        
        if ($isInNavigation !== null) {
            $pivotData['is_in_navigation'] = $isInNavigation;
        } else {
            // Get current value if exists, otherwise use global setting
            $existing = $category->languages()->where('language_id', $languageId)->first();
            $pivotData['is_in_navigation'] = $existing 
                ? $existing->pivot->is_in_navigation 
                : $category->show_in_navigation;
        }
        
        $category->languages()->syncWithoutDetaching([
            $languageId => $pivotData
        ]);
        
        // Clear cache
        $this->clearCategoryCache($category);
    }

    /**
     * Set visibility for multiple channels at once.
     *
     * @param  Category  $category
     * @param  array  $channelSettings  ['channel_id' => ['is_visible' => bool, 'is_in_navigation' => bool]]
     * @return void
     */
    public function setMultipleChannelVisibility(Category $category, array $channelSettings): void
    {
        $syncData = [];
        
        foreach ($channelSettings as $channelId => $settings) {
            $syncData[$channelId] = [
                'is_visible' => $settings['is_visible'] ?? true,
                'is_in_navigation' => $settings['is_in_navigation'] ?? true,
            ];
        }
        
        $category->channels()->sync($syncData);
        $this->clearCategoryCache($category);
    }

    /**
     * Set visibility for multiple languages at once.
     *
     * @param  Category  $category
     * @param  array  $languageSettings  ['language_id' => ['is_visible' => bool, 'is_in_navigation' => bool]]
     * @return void
     */
    public function setMultipleLanguageVisibility(Category $category, array $languageSettings): void
    {
        $syncData = [];
        
        foreach ($languageSettings as $languageId => $settings) {
            $syncData[$languageId] = [
                'is_visible' => $settings['is_visible'] ?? true,
                'is_in_navigation' => $settings['is_in_navigation'] ?? true,
            ];
        }
        
        $category->languages()->sync($syncData);
        $this->clearCategoryCache($category);
    }

    /**
     * Remove channel-specific visibility (revert to global settings).
     *
     * @param  Category  $category
     * @param  Channel|int  $channel
     * @return void
     */
    public function removeChannelVisibility(Category $category, $channel): void
    {
        $channelId = $channel instanceof Channel ? $channel->id : $channel;
        $category->channels()->detach($channelId);
        $this->clearCategoryCache($category);
    }

    /**
     * Remove language-specific visibility (revert to global settings).
     *
     * @param  Category  $category
     * @param  Language|int|string  $language
     * @return void
     */
    public function removeLanguageVisibility(Category $category, $language): void
    {
        $languageId = $this->resolveLanguageId($language);
        
        if ($languageId) {
            $category->languages()->detach($languageId);
            $this->clearCategoryCache($category);
        }
    }

    /**
     * Get all channels with their visibility settings for a category.
     *
     * @param  Category  $category
     * @return Collection
     */
    public function getChannelVisibility(Category $category): Collection
    {
        return Channel::all()->map(function ($channel) use ($category) {
            $pivot = $category->channels()->where('channel_id', $channel->id)->first();
            
            return [
                'channel' => $channel,
                'is_visible' => $pivot ? $pivot->pivot->is_visible : $category->is_active,
                'is_in_navigation' => $pivot ? $pivot->pivot->is_in_navigation : $category->show_in_navigation,
                'is_custom' => $pivot !== null,
            ];
        });
    }

    /**
     * Get all languages with their visibility settings for a category.
     *
     * @param  Category  $category
     * @return Collection
     */
    public function getLanguageVisibility(Category $category): Collection
    {
        return Language::all()->map(function ($language) use ($category) {
            $pivot = $category->languages()->where('language_id', $language->id)->first();
            
            return [
                'language' => $language,
                'is_visible' => $pivot ? $pivot->pivot->is_visible : $category->is_active,
                'is_in_navigation' => $pivot ? $pivot->pivot->is_in_navigation : $category->show_in_navigation,
                'is_custom' => $pivot !== null,
            ];
        });
    }

    /**
     * Resolve language ID from various input types.
     *
     * @param  Language|int|string  $language
     * @return int|null
     */
    protected function resolveLanguageId($language): ?int
    {
        if ($language instanceof Language) {
            return $language->id;
        }
        
        if (is_int($language)) {
            return $language;
        }
        
        if (is_string($language)) {
            $lang = Language::where('code', $language)->first();
            return $lang ? $lang->id : null;
        }
        
        return null;
    }

    /**
     * Clear category cache.
     *
     * @param  Category  $category
     * @return void
     */
    protected function clearCategoryCache(Category $category): void
    {
        \Illuminate\Support\Facades\Cache::forget("category.{$category->id}.breadcrumb");
        \Illuminate\Support\Facades\Cache::forget("category.{$category->id}.full_path");
        \Illuminate\Support\Facades\Cache::forget("category.slug.{$category->slug}");
        \Illuminate\Support\Facades\Cache::forget('categories.roots');
        \Illuminate\Support\Facades\Cache::forget('categories.flat.all');
        \Illuminate\Support\Facades\Cache::forget('categories.navigation.3');
    }
}

