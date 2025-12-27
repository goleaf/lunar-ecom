<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Lunar\Models\Language;
use Lunar\Models\Url;
use Symfony\Component\HttpFoundation\Response;

class CanonicalProductSlugRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if (is_string($slug) && ctype_digit($slug)) {
            $productId = (int) $slug;
            $canonicalSlug = $this->resolveCanonicalSlug($productId);

            if ($canonicalSlug && $canonicalSlug !== $slug) {
                return redirect()->route(
                    'frontend.products.show',
                    array_merge(['slug' => $canonicalSlug], $request->query()),
                    301
                );
            }
        }

        return $next($request);
    }

    private function resolveCanonicalSlug(int $productId): ?string
    {
        $language = Language::where('code', app()->getLocale())->first()
            ?? Language::getDefault();

        $query = Url::whereIn('element_type', [Product::morphName(), Product::class])
            ->where('element_id', $productId);

        if ($language) {
            $query->where('language_id', $language->id);
        }

        $url = $query->orderByDesc('default')->first();

        if (! $url && $language) {
            $url = Url::whereIn('element_type', [Product::morphName(), Product::class])
                ->where('element_id', $productId)
                ->orderByDesc('default')
                ->first();
        }

        return $url?->slug;
    }
}
