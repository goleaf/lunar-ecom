<?php

namespace App\Services;

use App\Models\UrlRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing URL redirects.
 */
class RedirectService
{
    /**
     * Find redirect for a given path or slug.
     *
     * @param  string  $path  The old path or slug to redirect from
     * @return UrlRedirect|null
     */
    public function findRedirect(string $path): ?UrlRedirect
    {
        // Normalize path (remove leading/trailing slashes)
        $path = trim($path, '/');
        
        // Try to find by full path first
        $redirect = Cache::remember(
            "redirect.path.{$path}",
            3600,
            function () use ($path) {
                return UrlRedirect::active()
                    ->byOldPath($path)
                    ->orWhere(function ($query) use ($path) {
                        // Also try matching by slug
                        $query->byOldSlug($path);
                    })
                    ->orderBy('hit_count', 'desc') // Prefer frequently used redirects
                    ->first();
            }
        );

        return $redirect;
    }

    /**
     * Create a redirect from old slug/path to new slug/path.
     *
     * @param  string  $oldSlug
     * @param  string  $newSlug
     * @param  string  $redirectType  301 or 302
     * @param  mixed  $redirectable  The model being redirected (Product, Category, etc.)
     * @param  string|null  $oldPath  Full old path (optional)
     * @param  string|null  $newPath  Full new path (optional)
     * @param  int|null  $languageId  Language ID (optional)
     * @return UrlRedirect
     */
    public function createRedirect(
        string $oldSlug,
        string $newSlug,
        string $redirectType = '301',
        $redirectable = null,
        ?string $oldPath = null,
        ?string $newPath = null,
        ?int $languageId = null
    ): UrlRedirect {
        // Check if redirect already exists
        $existing = UrlRedirect::where('old_slug', $oldSlug)
            ->where('new_slug', $newSlug)
            ->first();

        if ($existing) {
            return $existing;
        }

        $redirect = UrlRedirect::create([
            'old_slug' => $oldSlug,
            'new_slug' => $newSlug,
            'old_path' => $oldPath,
            'new_path' => $newPath,
            'redirect_type' => $redirectType,
            'redirectable_type' => $redirectable ? get_class($redirectable) : null,
            'redirectable_id' => $redirectable?->id,
            'language_id' => $languageId,
            'is_active' => true,
        ]);

        // Clear cache
        $this->clearCache($oldSlug, $oldPath);

        return $redirect;
    }

    /**
     * Create redirect when a product URL slug changes.
     *
     * @param  \Lunar\Models\Product  $product
     * @param  string  $oldSlug
     * @param  string  $newSlug
     * @param  int|null  $languageId
     * @return UrlRedirect
     */
    public function createProductRedirect($product, string $oldSlug, string $newSlug, ?int $languageId = null): UrlRedirect
    {
        $oldPath = "/products/{$oldSlug}";
        $newPath = "/products/{$newSlug}";

        return $this->createRedirect(
            $oldSlug,
            $newSlug,
            '301', // Permanent redirect for SEO
            $product,
            $oldPath,
            $newPath,
            $languageId
        );
    }

    /**
     * Create redirect when a category URL slug changes.
     *
     * @param  \App\Models\Category  $category
     * @param  string  $oldSlug
     * @param  string  $newSlug
     * @param  int|null  $languageId
     * @return UrlRedirect
     */
    public function createCategoryRedirect($category, string $oldSlug, string $newSlug, ?int $languageId = null): UrlRedirect
    {
        $oldPath = "/categories/{$oldSlug}";
        $newPath = "/categories/{$newSlug}";

        return $this->createRedirect(
            $oldSlug,
            $newSlug,
            '301',
            $category,
            $oldPath,
            $newPath,
            $languageId
        );
    }

    /**
     * Handle redirect for a request.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function handleRedirect(Request $request)
    {
        $path = trim($request->path(), '/');
        
        $redirect = $this->findRedirect($path);
        
        if (!$redirect || !$redirect->is_active) {
            return null;
        }

        // Record the hit
        $redirect->recordHit();

        // Determine redirect URL
        $redirectUrl = $redirect->new_path ?? route('storefront.products.show', $redirect->new_slug);
        
        // If we have a query string, preserve it
        if ($request->getQueryString()) {
            $redirectUrl .= '?' . $request->getQueryString();
        }

        // Return redirect response
        $statusCode = $redirect->isPermanent() ? 301 : 302;
        
        return redirect($redirectUrl, $statusCode);
    }

    /**
     * Bulk create redirects from array.
     *
     * @param  array  $redirects  Array of ['old_slug' => 'new_slug', ...]
     * @param  string  $redirectType
     * @return int  Number of redirects created
     */
    public function bulkCreateRedirects(array $redirects, string $redirectType = '301'): int
    {
        $count = 0;
        
        foreach ($redirects as $oldSlug => $newSlug) {
            try {
                $this->createRedirect($oldSlug, $newSlug, $redirectType);
                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to create redirect from {$oldSlug} to {$newSlug}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Clear redirect cache.
     *
     * @param  string|null  $oldSlug
     * @param  string|null  $oldPath
     * @return void
     */
    public function clearCache(?string $oldSlug = null, ?string $oldPath = null): void
    {
        if ($oldSlug) {
            Cache::forget("redirect.path.{$oldSlug}");
        }
        
        if ($oldPath) {
            $path = trim($oldPath, '/');
            Cache::forget("redirect.path.{$path}");
        }
    }

    /**
     * Get redirect statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => UrlRedirect::count(),
            'active' => UrlRedirect::active()->count(),
            'permanent' => UrlRedirect::permanent()->count(),
            'temporary' => UrlRedirect::temporary()->count(),
            'total_hits' => UrlRedirect::sum('hit_count'),
            'top_redirects' => UrlRedirect::active()
                ->orderBy('hit_count', 'desc')
                ->limit(10)
                ->get(['old_slug', 'new_slug', 'hit_count', 'last_hit_at']),
        ];
    }
}

