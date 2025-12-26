<?php

namespace App\Lunar\Brands;

use Illuminate\Support\Collection;
use Lunar\Models\Brand;

/**
 * Helper class for working with Lunar Brands.
 * 
 * Provides convenience methods for managing brands.
 */
class BrandHelper
{
    /**
     * Get all brands.
     * 
     * @return Collection<Brand>
     */
    public static function getAll(): Collection
    {
        return Brand::orderBy('name')->get();
    }

    /**
     * Get brands grouped by first letter (A-Z).
     * 
     * @return array Array with letter keys and brand collections
     */
    public static function getGroupedByLetter(): array
    {
        $brands = static::getAll();
        $grouped = [];

        foreach ($brands as $brand) {
            $firstLetter = strtoupper(substr($brand->name, 0, 1));
            
            // Handle non-alphabetic characters
            if (!ctype_alpha($firstLetter)) {
                $firstLetter = '#';
            }

            if (!isset($grouped[$firstLetter])) {
                $grouped[$firstLetter] = collect();
            }

            $grouped[$firstLetter]->push($brand);
        }

        // Sort by letter
        ksort($grouped);

        return $grouped;
    }

    /**
     * Get all available letters that have brands.
     * 
     * @return array Array of letters
     */
    public static function getAvailableLetters(): array
    {
        $grouped = static::getGroupedByLetter();
        return array_keys($grouped);
    }

    /**
     * Get brands for a specific letter.
     * 
     * @param string $letter Single letter (A-Z) or '#' for non-alphabetic
     * @return Collection<Brand>
     */
    public static function getByLetter(string $letter): Collection
    {
        $letter = strtoupper($letter);
        $grouped = static::getGroupedByLetter();
        
        return $grouped[$letter] ?? collect();
    }

    /**
     * Find a brand by ID.
     * 
     * @param int $id
     * @return Brand|null
     */
    public static function find(int $id): ?Brand
    {
        return Brand::find($id);
    }

    /**
     * Find a brand by name (case-insensitive).
     * 
     * @param string $name
     * @return Brand|null
     */
    public static function findByName(string $name): ?Brand
    {
        return Brand::where('name', 'like', $name)->first();
    }

    /**
     * Get brand logo URL.
     * 
     * @param Brand $brand
     * @param string $conversion Optional media conversion name
     * @return string|null
     */
    public static function getLogoUrl(Brand $brand, string $conversion = 'thumb'): ?string
    {
        // Try to get logo from media
        $logo = $brand->getFirstMedia('logo');
        
        if ($logo) {
            try {
                return $logo->getUrl($conversion);
            } catch (\Throwable $e) {
                return $logo->getUrl();
            }
        }

        // Try to get from attribute_data if stored there
        $logoUrl = $brand->translateAttribute('logo_url');
        if ($logoUrl) {
            return $logoUrl;
        }

        return null;
    }

    /**
     * Get brand description.
     * 
     * @param Brand $brand
     * @param string|null $locale Optional locale
     * @return string|null
     */
    public static function getDescription(Brand $brand, ?string $locale = null): ?string
    {
        return $brand->translateAttribute('description', $locale);
    }

    /**
     * Get brand website URL.
     * 
     * @param Brand $brand
     * @return string|null
     */
    public static function getWebsiteUrl(Brand $brand): ?string
    {
        return $brand->translateAttribute('website_url');
    }

    /**
     * Get products for a brand.
     * 
     * @param Brand $brand
     * @param int $limit Optional limit
     * @return Collection
     */
    public static function getProducts(Brand $brand, ?int $limit = null): Collection
    {
        $query = $brand->products()
            ->published()
            ->with(['variants', 'variants.prices'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get product count for a brand.
     * 
     * @param Brand $brand
     * @return int
     */
    public static function getProductCount(Brand $brand): int
    {
        return $brand->products()
            ->published()
            ->count();
    }
}
