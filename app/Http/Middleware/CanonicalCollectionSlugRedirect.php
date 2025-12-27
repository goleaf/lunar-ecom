<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Url;
use Symfony\Component\HttpFoundation\Response;

class CanonicalCollectionSlugRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if (is_string($slug) && ctype_digit($slug)) {
            $collectionId = (int) $slug;
            $canonicalSlug = $this->resolveCanonicalSlug($collectionId);

            if ($canonicalSlug && $canonicalSlug !== $slug) {
                return redirect()->route(
                    'frontend.collections.show',
                    array_merge(['slug' => $canonicalSlug], $request->query()),
                    301
                );
            }
        }

        return $next($request);
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
