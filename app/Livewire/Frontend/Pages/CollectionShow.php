<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\CollectionController;
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
        return app(CollectionController::class)->show($this->slug, request());
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


