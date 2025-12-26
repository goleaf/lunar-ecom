<?php

namespace App\Livewire\Frontend\Pages;

use App\Services\SEOService;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Collection;

class CollectionsIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $collections = Collection::with(['group', 'urls'])
            ->whereHas('products')
            ->latest()
            ->paginate(12);

        $metaTags = SEOService::getDefaultMetaTags(
            'Collections',
            'Browse our product collections. Discover curated selections of products.',
            null,
            request()->url()
        );

        $pageMeta = new HtmlString(view('frontend.collections._meta', [
            'metaTags' => $metaTags,
        ])->render());

        return view('livewire.frontend.pages.collections-index', [
            'collections' => $collections,
        ])->layout('frontend.layout', [
            'pageTitle' => $metaTags['title'] ?? 'Collections',
            'pageMeta' => $pageMeta,
        ]);
    }
}


