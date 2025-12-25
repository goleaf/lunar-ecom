<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;

/**
 * General SEO service for common SEO operations.
 */
class SEOService
{
    /**
     * Get site name from config.
     * 
     * @return string
     */
    public static function getSiteName(): string
    {
        return config('app.name', 'Lunar Store');
    }

    /**
     * Get site URL.
     * 
     * @return string
     */
    public static function getSiteUrl(): string
    {
        return config('app.url', url('/'));
    }

    /**
     * Generate canonical URL from current request.
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return string
     */
    public static function getCanonicalUrl($request = null): string
    {
        if (!$request) {
            $request = request();
        }

        // Remove query parameters for canonical URL
        return $request->url();
    }

    /**
     * Get default meta tags for pages.
     * 
     * @param string $title
     * @param string|null $description
     * @param string|null $image
     * @param string|null $url
     * @return array
     */
    public static function getDefaultMetaTags(
        string $title,
        ?string $description = null,
        ?string $image = null,
        ?string $url = null
    ): array {
        $siteName = static::getSiteName();
        $siteUrl = static::getSiteUrl();
        
        $fullTitle = $title . ' - ' . $siteName;
        $description = $description ?? "Shop at {$siteName} for the best products.";
        $image = $image ?? $siteUrl . '/images/default-og-image.jpg';
        $url = $url ?? static::getCanonicalUrl();

        return [
            'title' => $fullTitle,
            'description' => $description,
            'og:title' => $title,
            'og:description' => $description,
            'og:image' => $image,
            'og:type' => 'website',
            'og:url' => $url,
            'og:site_name' => $siteName,
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $image,
            'canonical' => $url,
        ];
    }

    /**
     * Generate breadcrumb structured data.
     * 
     * @param array $breadcrumbs Array of ['name' => string, 'url' => string]
     * @return array
     */
    public static function generateBreadcrumbStructuredData(array $breadcrumbs): array
    {
        if (empty($breadcrumbs)) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(function ($item, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ];
            }, $breadcrumbs, array_keys($breadcrumbs)),
        ];
    }

    /**
     * Generate organization structured data.
     * 
     * @return array
     */
    public static function generateOrganizationStructuredData(): array
    {
        $siteUrl = static::getSiteUrl();
        $siteName = static::getSiteName();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $siteUrl,
            'logo' => $siteUrl . '/images/logo.png',
        ];
    }

    /**
     * Generate website structured data.
     * 
     * @return array
     */
    public static function generateWebsiteStructuredData(): array
    {
        $siteUrl = static::getSiteUrl();
        $siteName = static::getSiteName();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $siteUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}

