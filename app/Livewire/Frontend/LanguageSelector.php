<?php

namespace App\Livewire\Storefront;

use App\Lunar\Languages\LanguageHelper;
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

class LanguageSelector extends Component
{
    /**
     * @var array<int, array{code: string, name: string, is_default: bool}>
     */
    public array $languages = [];

    public string $currentCode = 'en';

    public function mount(): void
    {
        $this->languages = LanguageHelper::getAll()
            ->map(fn ($language) => [
                'code' => $language->code,
                'name' => $language->name,
                'is_default' => (bool) $language->default,
            ])
            ->values()
            ->all();

        $current = StorefrontSessionHelper::getLanguage();
        if ($current) {
            $this->currentCode = $current->code;
            return;
        }

        $default = LanguageHelper::getDefault();
        if ($default) {
            $this->currentCode = $default->code;
        }
    }

    public function switchLanguage(string $languageCode): void
    {
        $languageCode = strtolower(trim($languageCode));
        $language = LanguageHelper::findByCode($languageCode);

        if (!$language) {
            return;
        }

        StorefrontSessionHelper::setLanguage($language);
        $this->currentCode = $language->code;

        Cookie::queue(Cookie::make(
            'site_locale',
            $language->code,
            now()->addDays(365)->diffInMinutes()
        ));

        // Locale-prefixed routes (like /{locale}/r/{code}): preserve route params + query.
        $route = request()->route();
        $routeName = $route?->getName();
        $params = $route?->parameters() ?? [];

        if (array_key_exists('locale', $params) && $routeName) {
            $params['locale'] = $language->code;
            $url = route($routeName, $params);

            $query = request()->query();
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            $this->redirect($url, navigate: true);
            return;
        }

        // Otherwise, just reload current URL so translations everywhere update.
        $this->redirect(request()->fullUrl(), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.language-selector');
    }
}


