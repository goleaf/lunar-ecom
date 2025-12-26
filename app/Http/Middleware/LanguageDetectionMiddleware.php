<?php

namespace App\Http\Middleware;

use App\Lunar\Languages\LanguageHelper;
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for automatic language detection.
 * 
 * Detects language from:
 * 1. URL locale (/{locale}/...) or URL parameter (?lang=xx)
 * 2. User saved preference (if present on the user model)
 * 3. Cookie (site_locale)
 * 4. Session (storefront_language)
 * 5. Browser Accept-Language header
 * 6. Default language
 */
class LanguageDetectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $detectedLanguage = null;

        // 1) URL locale prefix (/{locale}/...)
        $routeLocale = $request->route('locale');
        if (is_string($routeLocale) && $routeLocale !== '') {
            $detectedLanguage = LanguageHelper::findByCode(strtolower($routeLocale));
        }

        // 1b) URL parameter (?lang=xx) for backward compatibility
        if (!$detectedLanguage && $request->has('lang')) {
            $langCode = (string) $request->get('lang');
            $detectedLanguage = LanguageHelper::findByCode(strtolower($langCode));
        }

        // 2) User preference (if the project stores it on the user model)
        if (!$detectedLanguage && auth()->check()) {
            $user = auth()->user();
            $preferred = $user?->getAttribute('locale')
                ?: $user?->getAttribute('language')
                ?: $user?->getAttribute('preferred_locale')
                ?: $user?->getAttribute('preferred_language');

            if (is_string($preferred) && $preferred !== '') {
                $detectedLanguage = LanguageHelper::findByCode(strtolower($preferred));
            }
        }

        // 3) Cookie preference
        if (!$detectedLanguage) {
            $cookieLocale = $request->cookie('site_locale');
            if (is_string($cookieLocale) && $cookieLocale !== '') {
                $detectedLanguage = LanguageHelper::findByCode(strtolower($cookieLocale));
            }
        }

        // 4) Session (storefront_language)
        if (!$detectedLanguage && session()->has('storefront_language')) {
            $sessionLocale = (string) session('storefront_language');
            if ($sessionLocale !== '') {
                $detectedLanguage = LanguageHelper::findByCode(strtolower($sessionLocale));
            }
        }

        // 5) Browser Accept-Language header
        if (!$detectedLanguage && $request->hasHeader('Accept-Language')) {
            $detectedLanguage = $this->detectFromBrowser($request);
        }

        // 6) Default language fallback
        if (!$detectedLanguage) {
            $detectedLanguage = LanguageHelper::getDefault();
        }

        if ($detectedLanguage) {
            StorefrontSessionHelper::setLanguage($detectedLanguage);

            // Persist preference cookie so future visits are deterministic.
            Cookie::queue(Cookie::make(
                'site_locale',
                $detectedLanguage->code,
                now()->addDays(365)->diffInMinutes()
            ));
        }

        return $next($request);
    }

    /**
     * Detect language from browser Accept-Language header.
     * 
     * @param Request $request
     * @return \Lunar\Models\Language|null
     */
    protected function detectFromBrowser(Request $request): ?\Lunar\Models\Language
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header (e.g., "en-US,en;q=0.9,fr;q=0.8")
        $languages = [];
        $parts = explode(',', $acceptLanguage);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ';') !== false) {
                [$lang, $q] = explode(';', $part);
                $q = (float) str_replace('q=', '', $q);
            } else {
                $lang = $part;
                $q = 1.0;
            }
            
            // Extract language code (e.g., "en-US" -> "en")
            $langCode = strtolower(explode('-', trim($lang))[0]);
            $languages[$langCode] = $q;
        }

        // Sort by quality (q value)
        arsort($languages);

        // Try to find matching language
        foreach (array_keys($languages) as $langCode) {
            $language = LanguageHelper::findByCode($langCode);
            if ($language) {
                return $language;
            }
        }

        return null;
    }
}

