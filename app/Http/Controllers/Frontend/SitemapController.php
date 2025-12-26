<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Lunar\Models\Product;
use Lunar\Models\Collection;
use App\Models\Category;
use App\Lunar\Products\ProductSEO;
use App\Lunar\Categories\CategorySEO;

/**
 * XML Sitemap controller for SEO.
 */
class SitemapController extends Controller
{
    /**
     * Maximum URLs per sitemap file (Google recommends 50,000, but we'll use 10,000 for performance).
     */
    const MAX_URLS_PER_SITEMAP = 10000;

    /**
     * Generate main sitemap index.
     * 
     * @return Response
     */
    public function index()
    {
        $sitemaps = [];
        $baseUrl = config('app.url');

        // Products sitemap
        $productCount = Product::published()->count();
        if ($productCount > 0) {
            $pages = ceil($productCount / self::MAX_URLS_PER_SITEMAP);
            for ($i = 1; $i <= $pages; $i++) {
                $sitemaps[] = [
                    'loc' => $baseUrl . '/sitemap-products-' . $i . '.xml',
                    'lastmod' => now()->toIso8601String(),
                ];
            }
        }

        // Categories sitemap
        $categoryCount = Category::where('is_active', true)->count();
        if ($categoryCount > 0) {
            $pages = ceil($categoryCount / self::MAX_URLS_PER_SITEMAP);
            for ($i = 1; $i <= $pages; $i++) {
                $sitemaps[] = [
                    'loc' => $baseUrl . '/sitemap-categories-' . $i . '.xml',
                    'lastmod' => now()->toIso8601String(),
                ];
            }
        }

        // Collections sitemap
        $collectionCount = Collection::count();
        if ($collectionCount > 0) {
            $pages = ceil($collectionCount / self::MAX_URLS_PER_SITEMAP);
            for ($i = 1; $i <= $pages; $i++) {
                $sitemaps[] = [
                    'loc' => $baseUrl . '/sitemap-collections-' . $i . '.xml',
                    'lastmod' => now()->toIso8601String(),
                ];
            }
        }

        // Static pages sitemap
        $sitemaps[] = [
            'loc' => $baseUrl . '/sitemap-static.xml',
            'lastmod' => now()->toIso8601String(),
        ];

        $xml = view('sitemap.index', ['sitemaps' => $sitemaps])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Generate products sitemap.
     * 
     * @param int $page
     * @return Response
     */
    public function products(int $page = 1)
    {
        $products = Product::published()
            ->with(['urls'])
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * self::MAX_URLS_PER_SITEMAP)
            ->take(self::MAX_URLS_PER_SITEMAP)
            ->get();

        $urls = $products->map(function ($product) {
            return ProductSEO::getSitemapEntry($product);
        })->toArray();

        $xml = view('sitemap.urlset', ['urls' => $urls])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Generate categories sitemap.
     * 
     * @param int $page
     * @return Response
     */
    public function categories(int $page = 1)
    {
        $categories = Category::where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * self::MAX_URLS_PER_SITEMAP)
            ->take(self::MAX_URLS_PER_SITEMAP)
            ->get();

        $urls = $categories->map(function ($category) {
            return CategorySEO::getSitemapEntry($category);
        })->toArray();

        $xml = view('sitemap.urlset', ['urls' => $urls])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Generate collections sitemap.
     * 
     * @param int $page
     * @return Response
     */
    public function collections(int $page = 1)
    {
        $collections = Collection::with(['urls'])
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * self::MAX_URLS_PER_SITEMAP)
            ->take(self::MAX_URLS_PER_SITEMAP)
            ->get();

        $urls = $collections->map(function ($collection) {
            $defaultUrl = $collection->urls->where('default', true)->first();
            $url = $defaultUrl 
                ? route('frontend.collections.show', $defaultUrl->slug)
                : url('/collections/' . $collection->id);

            return [
                'url' => $url,
                'lastmod' => $collection->updated_at->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => 0.7,
            ];
        })->toArray();

        $xml = view('sitemap.urlset', ['urls' => $urls])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Generate static pages sitemap.
     * 
     * @return Response
     */
    public function static()
    {
        $baseUrl = config('app.url');
        
        $urls = [
            [
                'url' => $baseUrl,
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => 1.0,
            ],
            [
                'url' => route('frontend.products.index'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => 0.9,
            ],
            [
                'url' => route('frontend.collections.index'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => 0.8,
            ],
            [
                'url' => route('categories.index'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => 0.8,
            ],
            [
                'url' => route('frontend.brands.index'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => 0.7,
            ],
        ];

        $xml = view('sitemap.urlset', ['urls' => $urls])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}


