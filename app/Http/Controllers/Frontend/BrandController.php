<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Lunar\Brands\BrandHelper;
use Illuminate\Http\Request;
use Lunar\Models\Brand;

/**
 * Controller for handling brand pages in the frontend.
 */
class BrandController extends Controller
{
    /**
     * Display a listing of all brands (A-Z directory).
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $letter = $request->get('letter');
        
        if ($letter) {
            // Show brands for specific letter
            $brands = BrandHelper::getByLetter($letter);
            $groupedBrands = [$letter => $brands];
        } else {
            // Show all brands grouped by letter
            $groupedBrands = BrandHelper::getGroupedByLetter();
        }

        $availableLetters = BrandHelper::getAvailableLetters();
        $allBrands = BrandHelper::getAll();

        // Get SEO data
        $metaTags = \App\Services\SEOService::getDefaultMetaTags(
            'Brands',
            'Browse all brands. Find products from your favorite manufacturers.',
            null,
            request()->url()
        );

        return view('frontend.brands.index', compact(
            'groupedBrands',
            'availableLetters',
            'allBrands',
            'letter',
            'metaTags'
        ));
    }

    /**
     * Display the specified brand with its products.
     * 
     * @param string $slug Brand slug or ID
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function show(string $slug, Request $request)
    {
        // Try to find brand by slug first, then by ID
        $brand = Brand::query()
            ->where('name', 'like', str_replace('-', ' ', $slug))
            ->orWhere('id', $slug)
            ->firstOrFail();

        // Get products for this brand
        $perPage = 24;
        $products = $brand->products()
            ->published()
            ->with(['variants.prices', 'media', 'urls'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Get brand details
        $logoUrl = BrandHelper::getLogoUrl($brand);
        $description = BrandHelper::getDescription($brand);
        $websiteUrl = BrandHelper::getWebsiteUrl($brand);
        $productCount = $products->total();

        // Get SEO data
        $canonicalUrl = route('frontend.brands.show', $brand->id);
        $metaDescription = $description 
            ? mb_substr(strip_tags($description), 0, 160)
            : "Browse {$productCount} products from {$brand->name}. High quality products with fast shipping.";

        $metaTags = [
            'title' => $brand->name . ' - Brands',
            'description' => $metaDescription,
            'og:title' => $brand->name,
            'og:description' => $metaDescription,
            'og:image' => $logoUrl,
            'og:type' => 'website',
            'og:url' => $canonicalUrl,
            'canonical' => $canonicalUrl,
        ];

        return view('frontend.brands.show', compact(
            'brand',
            'products',
            'logoUrl',
            'description',
            'websiteUrl',
            'productCount',
            'metaTags'
        ));
    }

    /**
     * Get brands for API/AJAX requests.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function api()
    {
        $brands = BrandHelper::getAll()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'logo_url' => BrandHelper::getLogoUrl($brand),
                    'product_count' => BrandHelper::getProductCount($brand),
                ];
            });

        return response()->json(['brands' => $brands]);
    }
}


