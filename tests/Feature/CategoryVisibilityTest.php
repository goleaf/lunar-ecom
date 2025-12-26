<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Services\CategoryVisibilityService;
use Lunar\Models\Channel;
use Lunar\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryVisibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CategoryVisibilityService::class);
    }

    public function test_category_can_have_channel_visibility(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $channel = Channel::factory()->create();

        $this->service->setChannelVisibility($category, $channel, false);

        $this->assertFalse($category->isVisibleInChannel($channel));
        $this->assertTrue($category->isVisibleInChannel(Channel::factory()->create()));
    }

    public function test_category_channel_visibility_falls_back_to_global(): void
    {
        $category = Category::factory()->create(['is_active' => false]);
        $channel = Channel::factory()->create();

        // No channel-specific setting, should use global is_active
        $this->assertFalse($category->isVisibleInChannel($channel));

        $category->update(['is_active' => true]);
        $this->assertTrue($category->isVisibleInChannel($channel));
    }

    public function test_category_can_have_language_visibility(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $language = Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        $this->service->setLanguageVisibility($category, $language, false);

        $this->assertFalse($category->isVisibleInLanguage($language));
        $this->assertTrue($category->isVisibleInLanguage(Language::firstOrCreate(['code' => 'fr'], ['name' => 'French'])));
    }

    public function test_category_language_visibility_falls_back_to_global(): void
    {
        $category = Category::factory()->create(['is_active' => false]);
        $language = Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        // No language-specific setting, should use global is_active
        $this->assertFalse($category->isVisibleInLanguage($language));

        $category->update(['is_active' => true]);
        $this->assertTrue($category->isVisibleInLanguage($language));
    }

    public function test_category_scope_visible_in_channel(): void
    {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => false]);
        $channel = Channel::factory()->create();

        $this->service->setChannelVisibility($category2, $channel, true);

        $visible = Category::visibleInChannel($channel)->get();

        $this->assertTrue($visible->contains($category1));
        $this->assertTrue($visible->contains($category2));
    }

    public function test_category_scope_visible_in_language(): void
    {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => false]);
        $language = Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        $this->service->setLanguageVisibility($category2, $language, true);

        $visible = Category::visibleInLanguage($language)->get();

        $this->assertTrue($visible->contains($category1));
        $this->assertTrue($visible->contains($category2));
    }

    public function test_category_visibility_service_can_set_multiple_channels(): void
    {
        $category = Category::factory()->create();
        $channel1 = Channel::factory()->create();
        $channel2 = Channel::factory()->create();

        $this->service->setMultipleChannelVisibility($category, [
            $channel1->id => ['is_visible' => true, 'is_in_navigation' => false],
            $channel2->id => ['is_visible' => false, 'is_in_navigation' => true],
        ]);

        $this->assertTrue($category->isVisibleInChannel($channel1));
        $this->assertFalse($category->isVisibleInChannel($channel2));
        $this->assertFalse($category->isInNavigationForChannel($channel1));
        $this->assertTrue($category->isInNavigationForChannel($channel2));
    }
}

