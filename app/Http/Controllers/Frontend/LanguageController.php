<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Lunar\Languages\LanguageHelper;
use App\Lunar\FrontendSession\FrontendSessionHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Lunar\Models\Language;

/**
 * Controller for handling language switching in the frontend.
 */
class LanguageController extends Controller
{
    /**
     * Get all available languages.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $languages = LanguageHelper::getAll()
            ->map(function ($language) {
                return [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'is_default' => $language->default,
                ];
            });

        return response()->json([
            'languages' => $languages,
            'current' => $this->getCurrentLanguageData(),
        ]);
    }

    /**
     * Switch to a different language.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'language' => 'required|string',
        ]);

        $languageCode = $request->input('language');
        $language = LanguageHelper::findByCode($languageCode);

        if (!$language) {
            return response()->json([
                'error' => 'Language not found',
            ], 404);
        }

        // Set the language in the frontend session
        FrontendSessionHelper::setLanguage($language);

        // Persist cookie preference (used by deterministic locale resolution).
        Cookie::queue(Cookie::make(
            'site_locale',
            $language->code,
            now()->addDays(365)->diffInMinutes()
        ));

        return response()->json([
            'success' => true,
            'message' => __('frontend.messages.language_switched'),
            'language' => $this->getCurrentLanguageData(),
        ]);
    }

    /**
     * Get the current language.
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        return response()->json([
            'language' => $this->getCurrentLanguageData(),
        ]);
    }

    /**
     * Get current language data as array.
     *
     * @return array|null
     */
    protected function getCurrentLanguageData(): ?array
    {
        $language = FrontendSessionHelper::getLanguage();

        if (!$language) {
            return null;
        }

        return [
            'id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
            'is_default' => $language->default,
        ];
    }
}





