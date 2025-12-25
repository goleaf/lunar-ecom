<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\PromotionalBanner;
use Illuminate\Http\Request;

/**
 * Controller for homepage display.
 */
class HomepageController extends Controller
{
    /**
     * Display the homepage.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get featured collections
        $featuredCollections = Collection::homepage()
            ->with(['products' => function ($query) {
                $query->limit(8);
            }])
            ->get();

        // Get bestseller collection
        $bestsellers = Collection::ofType('bestsellers')
            ->active()
            ->with(['products' => function ($query) {
                $query->limit(12);
            }])
            ->first();

        // Get new arrivals collection
        $newArrivals = Collection::ofType('new_arrivals')
            ->active()
            ->with(['products' => function ($query) {
                $query->limit(12);
            }])
            ->first();

        // Get promotional banners (could be from database or config)
        $promotionalBanners = $this->getPromotionalBanners();

        return view('storefront.homepage.index', compact(
            'featuredCollections',
            'bestsellers',
            'newArrivals',
            'promotionalBanners'
        ));
    }

    /**
     * Get promotional banners.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getPromotionalBanners()
    {
        $banners = PromotionalBanner::active()
            ->orderBy('position')
            ->orderBy('order')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'description' => $banner->description,
                    'image' => $banner->getImageUrl('desktop') ?? asset('images/banners/default.jpg'),
                    'link' => $this->getBannerLink($banner),
                    'link_text' => $banner->link_text ?? 'Shop Now',
                    'position' => $banner->position,
                    'is_active' => $banner->isActive(),
                ];
            });

        // Fallback to example banners if none exist
        if ($banners->isEmpty()) {
            return collect([
                [
                    'id' => 1,
                    'title' => 'Summer Sale',
                    'subtitle' => 'Up to 50% Off',
                    'description' => 'Shop the best deals on summer essentials',
                    'image' => asset('images/banners/summer-sale.jpg'),
                    'link' => route('storefront.collections.index'),
                    'link_text' => 'Shop Now',
                    'position' => 'top',
                    'is_active' => true,
                ],
            ]);
        }

        return $banners;
    }

    /**
     * Get banner link based on link type.
     *
     * @param  PromotionalBanner  $banner
     * @return string
     */
    protected function getBannerLink(PromotionalBanner $banner): string
    {
        if ($banner->link) {
            return match ($banner->link_type) {
                'collection' => route('storefront.collections.show', $banner->link),
                'product' => route('storefront.products.show', $banner->link),
                'category' => route('storefront.categories.show', $banner->link),
                'url' => $banner->link,
                default => route('storefront.collections.show', $banner->link),
            };
        }

        return route('storefront.collections.index');
    }
}

