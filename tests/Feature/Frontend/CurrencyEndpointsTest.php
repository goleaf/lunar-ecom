<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Currency;
use Tests\TestCase;

class CurrencyEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_currency_index_returns_currencies_and_current(): void
    {
        $this->getJson(route('frontend.currency.index'))
            ->assertOk()
            ->assertJsonStructure([
                'currencies',
                'current',
            ]);
    }

    public function test_currency_current_returns_currency_payload(): void
    {
        $this->getJson(route('frontend.currency.current'))
            ->assertOk()
            ->assertJsonStructure([
                'currency' => ['code'],
            ]);
    }

    public function test_currency_switch_returns_404_for_unknown_currency(): void
    {
        $this->postJson(route('frontend.currency.switch'), [
            'currency' => 'ZZZ',
        ])
            ->assertStatus(404)
            ->assertJson([
                'error' => 'Currency not found',
            ]);
    }

    public function test_currency_switch_can_switch_to_enabled_currency(): void
    {
        Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'enabled' => true,
                'default' => true,
            ]
        );

        $this->postJson(route('frontend.currency.switch'), [
            'currency' => 'USD',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('currency.code', 'USD');
    }
}

