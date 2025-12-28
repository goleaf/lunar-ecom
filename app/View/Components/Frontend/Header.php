<?php

namespace App\View\Components\Frontend;

use App\Models\Category;
use App\Models\PromotionalBanner;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class Header extends Component
{
    public EloquentCollection $navCategories;

    /**
     * Optional promo banner shown inside the desktop mega menu.
     *
     * @var array{title:string,subtitle:?string,image:?string,link:string,link_text:string}|null
     */
    public ?array $megaMenuBanner = null;

    public function __construct()
    {
        // Root navigation categories (marketplace-style menu).
        $this->navCategories = Category::query()
            ->active()
            ->inNavigation()
            ->whereNull('parent_id')
            ->ordered()
            ->with([
                'children' => function ($query) {
                    $query
                        ->active()
                        ->inNavigation()
                        ->ordered()
                        ->limit(12);
                },
            ])
            ->limit(10)
            ->get();

        // Optional mega-menu promo banner.
        // Prefer an explicit "header" banner, else reuse the top promo banner, else any active banner.
        $banner = PromotionalBanner::query()
            ->active()
            ->with('media')
            ->where('position', 'header')
            ->orderBy('order')
            ->first();

        if (!$banner) {
            $banner = PromotionalBanner::query()
                ->active()
                ->with('media')
                ->where('position', 'top')
                ->orderBy('order')
                ->first();
        }

        if (!$banner) {
            $banner = PromotionalBanner::query()
                ->active()
                ->with('media')
                ->orderBy('position')
                ->orderBy('order')
                ->first();
        }

        if ($banner) {
            $this->megaMenuBanner = [
                'title' => $banner->title ?: 'Special offers',
                'subtitle' => $banner->subtitle,
                'image' => $banner->getImageUrl('tablet') ?? $banner->getImageUrl('desktop'),
                'link' => $this->resolveBannerLink($banner),
                'link_text' => $banner->link_text ?: 'Shop now',
            ];
        }
    }

    protected function resolveBannerLink(PromotionalBanner $banner): string
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

    public function render(): View
    {
        return view('components.frontend.header');
    }
}

