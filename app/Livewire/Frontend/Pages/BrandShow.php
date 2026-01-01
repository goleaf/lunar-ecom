<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Brands\BrandHelper;
use Livewire\Component;
use Lunar\Models\Brand;

class BrandShow extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $slug = $this->slug;

        // Try to find brand by slug-ish name first, then by ID.
        $brand = Brand::query()
            ->where('name', 'like', str_replace('-', ' ', $slug))
            ->orWhere('id', $slug)
            ->firstOrFail();

        $perPage = 24;
        $products = $brand->products()
            ->published()
            ->with(['variants.prices', 'media', 'urls'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $logoUrl = BrandHelper::getLogoUrl($brand);
        $description = BrandHelper::getDescription($brand);
        $websiteUrl = BrandHelper::getWebsiteUrl($brand);
        $productCount = $products->total();

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
}


