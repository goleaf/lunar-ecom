<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Lunar\Languages\LanguageHelper;
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Models\Language;

/**
 * Controller for handling language switching in the storefront.
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

        // Set the language in the storefront session
        StorefrontSessionHelper::setLanguage($language);

        return response()->json([
            'success' => true,
            'message' => 'Language switched successfully',
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
        $language = StorefrontSessionHelper::getLanguage();
        
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

