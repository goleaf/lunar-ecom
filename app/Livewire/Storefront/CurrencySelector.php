<?php

namespace App\Livewire\Storefront;

use App\Lunar\Currencies\CurrencyHelper;
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use Livewire\Component;
use Lunar\Models\Currency;

class CurrencySelector extends Component
{
    /**
     * @var array<int, array{code: string, name: string, is_default: bool}>
     */
    public array $currencies = [];

    public string $currentCode = 'USD';

    public function mount(): void
    {
        $this->currencies = CurrencyHelper::getEnabled()
            ->map(fn ($currency) => [
                'code' => $currency->code,
                'name' => $currency->name,
                'is_default' => (bool) $currency->default,
            ])
            ->values()
            ->all();

        $current = StorefrontSessionHelper::getCurrency();
        if ($current) {
            $this->currentCode = $current->code;
            return;
        }

        $default = Currency::getDefault();
        if ($default) {
            $this->currentCode = $default->code;
        } elseif (!empty($this->currencies)) {
            $this->currentCode = $this->currencies[0]['code'];
        }
    }

    public function switchCurrency(string $currencyCode): void
    {
        $currencyCode = strtoupper(trim($currencyCode));
        $currency = CurrencyHelper::findByCode($currencyCode);

        if (!$currency || !$currency->enabled) {
            return;
        }

        StorefrontSessionHelper::setCurrency($currency);
        $this->currentCode = $currency->code;

        // Reload current URL so prices update everywhere.
        $this->redirect(request()->fullUrl(), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.currency-selector');
    }
}


