<?php

namespace Tests\Feature\Storefront;

use App\Livewire\Storefront\CurrencySelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lunar\Models\Currency;
use Tests\TestCase;

class CurrencySelectorLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1.0000,
                'format' => '{symbol}{value}',
                'decimal_point' => '.',
                'thousand_point' => ',',
                'decimal_places' => 2,
                'enabled' => true,
                'default' => true,
            ]
        );
        Currency::where('code', 'USD')->update(['enabled' => true, 'default' => true]);

        Currency::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'exchange_rate' => 0.9000,
                'format' => '{symbol}{value}',
                'decimal_point' => '.',
                'thousand_point' => ',',
                'decimal_places' => 2,
                'enabled' => true,
                'default' => false,
            ]
        );
        Currency::where('code', 'EUR')->update(['enabled' => true]);
    }

    public function test_it_renders_current_currency(): void
    {
        Livewire::test(CurrencySelector::class)
            ->assertSee('USD', false);
    }
}


