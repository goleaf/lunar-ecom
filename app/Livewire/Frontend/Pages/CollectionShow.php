<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Collections\CollectionHelper;
use App\Services\CollectionFilterOptionsService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Url;

class CollectionShow extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        if (ctype_digit($slug)) {
            $collectionId = (int) $slug;
            $canonicalSlug = $this->resolveCanonicalSlug($collectionId);

            if ($canonicalSlug && $canonicalSlug !== $slug) {
                $this->redirectRoute(
                    'frontend.collections.show',
                    array_merge(['slug' => $canonicalSlug], request()->query()),
                    navigate: true
                );
                return;
            }
        }

        $this->slug = $slug;
    }

    public function render()
    {
        $request = request();
        $slug = $this->slug;

        $url = Url::where('slug', $slug)
            ->whereIn('element_type', [Collection::morphName(), Collection::class])
            ->first();

        $collectionId = $url?->element_id;

        if (! $collectionId && ctype_digit($slug)) {
            $collectionId = (int) $slug;
        }

        if (! $collectionId) {
            abort(404);
        }

        $collection = Collection::with(['group', 'children', 'media', 'urls'])->findOrFail($collectionId);
        Gate::authorize('view', $collection);

        $products = CollectionHelper::getSortedProducts($collection);

        $perPage = 12;
        $currentPage = (int) $request->get('page', 1);
        $items = $products->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $products = new LengthAwarePaginator(
            $items,
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $breadcrumb = $collection->breadcrumb;

        $defaultUrl = $collection->urls->where('default', true)->first();
        $canonicalUrl = $defaultUrl
            ? route('frontend.collections.show', $defaultUrl->slug)
            : url('/collections/' . $collection->id);

        $collectionName = $collection->translate('name');
        $collectionDescription = $collection->translate('description');

        $metaTags = [
            'title' => $collectionName . ' - Collections',
            'description' => $collectionDescription
                ? mb_substr(strip_tags($collectionDescription), 0, 160)
                : "Browse products in {$collectionName} collection.",
            'og:title' => $collectionName,
            'og:description' => $collectionDescription
                ? mb_substr(strip_tags($collectionDescription), 0, 160)
                : "Browse products in {$collectionName} collection.",
            'og:image' => $collection->getFirstMediaUrl('images', 'large'),
            'og:type' => 'website',
            'og:url' => $canonicalUrl,
            'canonical' => $canonicalUrl,
        ];

        $filterOptions = app(CollectionFilterOptionsService::class)->getFilterOptions($collection, $request);

        return view('frontend.collections.show', compact(
            'collection',
            'products',
            'breadcrumb',
            'metaTags',
            'filterOptions'
        ));
    }

    private function resolveCanonicalSlug(int $collectionId): ?string
    {
        $language = Language::where('code', app()->getLocale())->first()
            ?? Language::getDefault();

        $query = Url::whereIn('element_type', [Collection::morphName(), Collection::class])
            ->where('element_id', $collectionId);

        if ($language) {
            $query->where('language_id', $language->id);
        }

        $url = $query->orderByDesc('default')->first();

        if (! $url && $language) {
            $url = Url::whereIn('element_type', [Collection::morphName(), Collection::class])
                ->where('element_id', $collectionId)
                ->orderByDesc('default')
                ->first();
        }

        return $url?->slug;
    }
}


