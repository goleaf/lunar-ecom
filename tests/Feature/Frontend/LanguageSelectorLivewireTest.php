<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Frontend\LanguageSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lunar\Models\Language;
use Tests\TestCase;

class LanguageSelectorLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Language::where('code', 'en')->update(['default' => true]);
        Language::firstOrCreate(['code' => 'ru'], ['name' => 'Russian', 'default' => false]);
    }

    public function test_it_renders_current_language(): void
    {
        Livewire::test(LanguageSelector::class)
            ->assertSee('EN', false);
    }

    public function test_it_can_switch_language_and_sets_session(): void
    {
        Livewire::test(LanguageSelector::class)
            ->call('switchLanguage', 'ru')
            ->assertSessionHas('frontend_language', 'ru');
    }
}


