<?php

namespace App\Lunar\Categories;

use App\Models\Category;
use App\Lunar\Categories\CategoryHelper;

/**
 * SEO helper for categories.
 * 
 * Provides methods for generating SEO-friendly URLs, meta tags, and structured data.
 */
class CategorySEO
{
    /**
     * Get SEO meta tags for category.
     * 
     * @param Category $category
     * @return array
     */
    public static function getMetaTags(Category $category): array
    {
        return [
            'title' => $category->meta_title ?? $category->getName(),
            'description' => $category->meta_description ?? static::generateDescription($category),
            'keywords' => static::generateKeywords($category),
            'og:title' => $category->meta_title ?? $category->getName(),
            'og:description' => $category->meta_description ?? static::generateDescription($category),
            'og:image' => $category->getImageUrl('large'),
            'og:type' => 'website',
            'og:url' => CategoryHelper::getUrl($category),
            'canonical' => CategoryHelper::getUrl($category),
        ];
    }

    /**
     * Generate meta description from category.
     * 
     * @param Category $category
     * @return string
     */
    protected static function generateDescription(Category $category): string
    {
        $description = $category->getDescription();
        
        if ($description) {
            // Strip HTML and limit length
            $text = strip_tags($description);
            return mb_substr($text, 0, 160);
        }

        // Fallback description
        $productCount = $category->product_count;
        return "Browse {$productCount} products in {$category->getName()}. Find the best deals and latest items.";
    }

    /**
     * Generate keywords from category.
     * 
     * @param Category $category
     * @return string
     */
    protected static function generateKeywords(Category $category): string
    {
        $keywords = [$category->getName()];
        
        // Add parent category names
        foreach ($category->ancestors as $ancestor) {
            $keywords[] = $ancestor->getName();
        }

        return implode(', ', $keywords);
    }

    /**
     * Get structured data (JSON-LD) for category.
     * 
     * @param Category $category
     * @return array
     */
    public static function getStructuredData(Category $category): array
    {
        $breadcrumb = CategoryHelper::getBreadcrumb($category);
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'url' => CategoryHelper::getUrl($category),
        ];

        // Add breadcrumb structured data
        if (count($breadcrumb) > 1) {
            $structuredData['breadcrumb'] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(function ($item, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $item['name'],
                        'item' => $item['url'],
                    ];
                }, $breadcrumb, array_keys($breadcrumb)),
            ];
        }

        // Add image if available
        if ($imageUrl = $category->getImageUrl('large')) {
            $structuredData['image'] = $imageUrl;
        }

        return $structuredData;
    }

    /**
     * Generate sitemap entry for category.
     * 
     * @param Category $category
     * @return array
     */
    public static function getSitemapEntry(Category $category): array
    {
        return [
            'url' => CategoryHelper::getUrl($category),
            'lastmod' => $category->updated_at->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => static::calculatePriority($category),
        ];
    }

    /**
     * Calculate sitemap priority based on category depth.
     * 
     * @param Category $category
     * @return float
     */
    protected static function calculatePriority(Category $category): float
    {
        $depth = $category->depth;
        
        // Root categories get higher priority
        if ($depth === 0) {
            return 0.9;
        }
        
        // Decrease priority with depth
        return max(0.3, 0.9 - ($depth * 0.1));
    }

    /**
     * Get robots meta tag value.
     * 
     * @param Category $category
     * @return string
     */
    public static function getRobotsMeta(Category $category): string
    {
        if (!$category->is_active) {
            return 'noindex, nofollow';
        }

        return 'index, follow';
    }
}

