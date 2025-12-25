<?php

namespace App\Http\Middleware;

use App\Lunar\Languages\LanguageHelper;
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for automatic language detection.
 * 
 * Detects language from:
 * 1. URL parameter (?lang=xx)
 * 2. Browser Accept-Language header
 * 3. Session (if already set)
 * 4. Default language
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
        // Check if language is already set in session
        if (!session()->has('storefront_language')) {
            $detectedLanguage = null;

            // 1. Check URL parameter
            if ($request->has('lang')) {
                $langCode = $request->get('lang');
                $detectedLanguage = LanguageHelper::findByCode($langCode);
            }

            // 2. Detect from browser Accept-Language header
            if (!$detectedLanguage && $request->hasHeader('Accept-Language')) {
                $detectedLanguage = $this->detectFromBrowser($request);
            }

            // 3. Use default language if nothing detected
            if (!$detectedLanguage) {
                $detectedLanguage = LanguageHelper::getDefault();
            }

            // Set the detected language
            if ($detectedLanguage) {
                StorefrontSessionHelper::setLanguage($detectedLanguage);
            }
        } else {
            // Language is already set, just ensure locale is set
            $language = StorefrontSessionHelper::getLanguage();
            if ($language) {
                app()->setLocale($language->code);
            }
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

