<?php

namespace Tests\Feature;

use App\Livewire\Frontend\Pages\Homepage;
use App\Models\Collection;
use App\Models\PromotionalBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the homepage route returns a successful response.
     */
    public function test_homepage_route_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test that the homepage route uses the correct route name.
     */
    public function test_homepage_route_name(): void
    {
        $response = $this->get(route('frontend.homepage'));

        $response->assertStatus(200);
    }

    /**
     * Test that the homepage view is returned.
     */
    public function test_homepage_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('homepage', false);
    }

    /**
     * Test that the homepage view receives required data.
     */
    public function test_homepage_view_receives_required_data(): void
    {
        $component = Livewire::test(Homepage::class);

        $this->assertNotNull($component->get('featuredCollections'));
        $this->assertTrue(method_exists($component->get('featuredCollections'), 'count'));
        $this->assertTrue($component->get('promotionalBanners')->isNotEmpty());
    }

    /**
     * Test that featured collections are displayed on homepage.
     */
    public function test_homepage_displays_featured_collections(): void
    {
        // Create a featured collection
        $featuredCollection = Collection::factory()->create([
            'show_on_homepage' => true,
            'homepage_position' => 1,
        ]);

        $component = Livewire::test(Homepage::class);
        $featuredCollections = $component->get('featuredCollections');
        $this->assertTrue($featuredCollections->contains($featuredCollection));
    }

    /**
     * Test that bestsellers collection is displayed on homepage.
     */
    public function test_homepage_displays_bestsellers_collection(): void
    {
        // Create a bestsellers collection
        $bestsellers = Collection::factory()->create([
            'collection_type' => 'bestsellers',
        ]);

        $component = Livewire::test(Homepage::class);
        $bestsellersData = $component->get('bestsellers');
        $this->assertNotNull($bestsellersData);
        $this->assertEquals($bestsellers->id, $bestsellersData->id);
    }

    /**
     * Test that new arrivals collection is displayed on homepage.
     */
    public function test_homepage_displays_new_arrivals_collection(): void
    {
        // Create a new arrivals collection
        $newArrivals = Collection::factory()->create([
            'collection_type' => 'new_arrivals',
        ]);

        $component = Livewire::test(Homepage::class);
        $newArrivalsData = $component->get('newArrivals');
        $this->assertNotNull($newArrivalsData);
        $this->assertEquals($newArrivals->id, $newArrivalsData->id);
    }

    /**
     * Test that promotional banners are displayed on homepage.
     */
    public function test_homepage_displays_promotional_banners(): void
    {
        // Create a promotional banner
        $banner = PromotionalBanner::create([
            'title' => 'Test Banner',
            'subtitle' => 'Test Subtitle',
            'is_active' => true,
            'position' => 'top',
            'order' => 1,
        ]);

        $component = Livewire::test(Homepage::class);
        $promotionalBanners = $component->get('promotionalBanners');
        $this->assertNotEmpty($promotionalBanners);
    }

    /**
     * Test that inactive collections are not displayed on homepage.
     */
    public function test_homepage_excludes_inactive_collections(): void
    {
        // Create an inactive featured collection
        $inactiveCollection = Collection::factory()->create([
            'show_on_homepage' => true,
            'starts_at' => now()->addDays(7), // Future start date makes it inactive
        ]);

        $component = Livewire::test(Homepage::class);
        $featuredCollections = $component->get('featuredCollections');
        $this->assertFalse($featuredCollections->contains($inactiveCollection));
    }

    /**
     * Test that homepage works with no collections.
     */
    public function test_homepage_works_with_no_collections(): void
    {
        $component = Livewire::test(Homepage::class);
        $featuredCollections = $component->get('featuredCollections');
        $this->assertEmpty($featuredCollections);
    }

    /**
     * Test that homepage works with no promotional banners.
     */
    public function test_homepage_works_with_no_promotional_banners(): void
    {
        $component = Livewire::test(Homepage::class);
        $promotionalBanners = $component->get('promotionalBanners');
        // Should return fallback banners if none exist
        $this->assertNotEmpty($promotionalBanners);
    }

    /**
     * Test that homepage displays collections in correct order.
     */
    public function test_homepage_displays_collections_in_order(): void
    {
        // Create multiple featured collections with different positions
        $collection1 = Collection::factory()->create([
            'show_on_homepage' => true,
            'homepage_position' => 3,
        ]);
        
        $collection2 = Collection::factory()->create([
            'show_on_homepage' => true,
            'homepage_position' => 1,
        ]);
        
        $collection3 = Collection::factory()->create([
            'show_on_homepage' => true,
            'homepage_position' => 2,
        ]);

        $component = Livewire::test(Homepage::class);
        $featuredCollections = $component->get('featuredCollections');
        
        // Check that collections are ordered by homepage_position
        $this->assertEquals($collection2->id, $featuredCollections->first()->id);
        $this->assertEquals($collection1->id, $featuredCollections->last()->id);
    }
}


