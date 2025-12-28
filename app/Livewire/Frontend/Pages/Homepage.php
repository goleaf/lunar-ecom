<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\PromotionalBanner;
use App\Services\SEOService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class Homepage extends Component
{
    public EloquentCollection $featuredCollections;

    /**
     * Subset of featured collections used for the hero slider.
     * We only include collections that have a usable hero image.
     */
    public EloquentCollection $heroCollections;

    /**
     * Root categories shown in the homepage navigation blocks.
     */
    public EloquentCollection $navigationCategories;

    public ?Collection $bestsellers = null;

    public ?Collection $newArrivals = null;

    public SupportCollection $promotionalBanners;

    public function mount(): void
    {
        $this->featuredCollections = Collection::query()
            ->homepage()
            ->withCount('products')
            ->with([
                'urls',
                'media',
            ])
            ->get();

        $this->navigationCategories = Category::query()
            ->active()
            ->inNavigation()
            ->whereNull('parent_id')
            ->ordered()
            ->with('media')
            ->limit(12)
            ->get();

        // Only include collections that can actually render a hero image.
        // (Avoid mismatched slide indices/dots when collections don't have media.)
        $this->heroCollections = $this->featuredCollections
            ->filter(function (Collection $collection) {
                return (bool) ($collection->getFirstMedia('hero') ?? $collection->getFirstMedia('images'));
            })
            ->take(3)
            ->values();

        // Get bestseller collection
        $this->bestsellers = Collection::query()
            ->ofType('bestsellers')
            ->active()
            ->with([
                'urls',
                'media',
                'products' => function ($query) {
                    $query
                        ->with(['urls', 'media', 'variants'])
                        ->limit(12);
                },
            ])
            ->first();

        // Get new arrivals collection
        $this->newArrivals = Collection::query()
            ->ofType('new_arrivals')
            ->active()
            ->with([
                'urls',
                'media',
                'products' => function ($query) {
                    $query
                        ->with(['urls', 'media', 'variants'])
                        ->limit(12);
                },
            ])
            ->first();

        $this->promotionalBanners = $this->getPromotionalBanners();
    }

    public function render()
    {
        $metaTags = SEOService::getDefaultMetaTags(
            __('frontend.home'),
            __('frontend.homepage.meta_description', ['store' => SEOService::getSiteName()]),
            null,
            request()->url(),
        );

        $categorySpotlights = $this->getCategorySpotlights();

        $pageMeta = new HtmlString(view('frontend.homepage._meta', [
            'metaTags' => $metaTags,
        ])->render());

        return view('livewire.frontend.pages.homepage', [
            'featuredCollections' => $this->featuredCollections,
            'heroCollections' => $this->heroCollections,
            'navigationCategories' => $this->navigationCategories,
            'categorySpotlights' => $categorySpotlights,
            'bestsellers' => $this->bestsellers,
            'newArrivals' => $this->newArrivals,
            'promotionalBanners' => $this->promotionalBanners,
        ])->layout('frontend.layout', [
            'pageTitle' => $metaTags['title'] ?? SEOService::getSiteName(),
            'pageMeta' => $pageMeta,
            'mainClass' => 'max-w-none p-0',
        ]);
    }

    protected function getCategorySpotlights(int $categoryLimit = 6, int $productLimit = 12): SupportCollection
    {
        if (!isset($this->navigationCategories) || $this->navigationCategories->isEmpty()) {
            return collect();
        }

        return $this->navigationCategories
            ->take($categoryLimit)
            ->map(function (Category $category) use ($productLimit) {
                $categoryIds = $category->descendants()->pluck('id')->push($category->id);
                $categoryTable = $category->getTable();

                $products = Product::query()
                    ->published()
                    ->whereHas('categories', function ($query) use ($categoryIds, $categoryTable) {
                        $query->whereIn($categoryTable.'.id', $categoryIds);
                    })
                    ->with(['urls', 'media', 'variants'])
                    ->latest('published_at')
                    ->limit($productLimit)
                    ->get();

                return [
                    'category' => $category,
                    'products' => $products,
                ];
            })
            ->filter(fn (array $row) => $row['products']->isNotEmpty())
            ->values();
    }

    protected function getPromotionalBanners(): SupportCollection
    {
        $banners = PromotionalBanner::active()
            ->with('media')
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

        if ($banners->isEmpty()) {
            return collect([
                [
                    'id' => 1,
                    'title' => 'Summer Sale',
                    'subtitle' => 'Up to 50% Off',
                    'description' => 'Shop the best deals on summer essentials',
                    'image' => asset('images/banners/summer-sale.jpg'),
                    'link' => route('frontend.collections.index'),
                    'link_text' => 'Shop Now',
                    'position' => 'top',
                    'is_active' => true,
                ],
            ]);
        }

        return $banners;
    }

    protected function getBannerLink(PromotionalBanner $banner): string
    {
        if ($banner->link) {
            return match ($banner->link_type) {
                'collection' => route('frontend.collections.show', $banner->link),
                'product' => route('frontend.products.show', $banner->link),
                'category' => route('categories.show', $banner->link),
                'url' => $banner->link,
                default => route('frontend.collections.show', $banner->link),
            };
        }

        return route('frontend.collections.index');
    }
}



