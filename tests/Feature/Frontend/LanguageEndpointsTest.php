<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Language;
use Tests\TestCase;

class LanguageEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_index_returns_languages_and_current(): void
    {
        $this->getJson(route('frontend.language.index'))
            ->assertOk()
            ->assertJsonStructure([
                'languages',
                'current',
            ]);
    }

    public function test_language_current_returns_language_payload(): void
    {
        $this->getJson(route('frontend.language.current'))
            ->assertOk()
            ->assertJsonStructure([
                'language' => ['code'],
            ]);
    }

    public function test_language_switch_returns_404_for_unknown_language(): void
    {
        $this->postJson(route('frontend.language.switch'), [
            'language' => 'zz',
        ])
            ->assertStatus(404)
            ->assertJson([
                'error' => 'Language not found',
            ]);
    }

    public function test_language_switch_can_switch_to_existing_language(): void
    {
        Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'default' => true,
            ]
        );

        $this->postJson(route('frontend.language.switch'), [
            'language' => 'en',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('language.code', 'en');
    }
}

