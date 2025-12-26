<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Frontend\SearchMegaMenu;
use App\Models\Category;
use App\Models\Product;
use Database\Factories\BrandFactory;
use Illuminate\Support\Str;
use Laravel\Scout\ModelObserver;
use Livewire\Livewire;
use Tests\TestCase;

class SearchMegaMenuLivewireTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');

        if (class_exists(ModelObserver::class)) {
            ModelObserver::disableSyncingFor(Product::class);
            ModelObserver::disableSyncingFor(\Lunar\Models\Brand::class);
        }
    }

    public function test_megamenu_autocomplete_returns_grouped_results(): void
    {
        $category = Category::factory()->create([
            'name' => ['en' => 'Shoes'],
            'slug' => 'shoes',
            'is_active' => true,
        ]);

        $brand = BrandFactory::new()->withProfile('ShoeBrand')->create();

        $product = Product::factory()
            ->published()
            ->withBrand($brand)
            ->withAttributes(['name' => 'ShoeBrand Shoe'])
            ->create([
                'sku' => 'SHOE-123',
            ]);

        $product->categories()->syncWithoutDetaching([$category->id]);

        $component = Livewire::test(SearchMegaMenu::class)
            ->call('openDropdown')
            ->set('query', 'shoe')
            ->call('refresh')
            ->assertSet('open', true);

        $groups = $component->get('groups');

        $this->assertNotEmpty($groups['categories'], 'Expected at least 1 category match.');
        $this->assertNotEmpty($groups['brands'], 'Expected at least 1 brand match.');
        $this->assertNotEmpty($groups['products'], 'Expected at least 1 product match.');

        $this->assertSame('Shoes', $groups['categories'][0]['title']);
        $this->assertSame('ShoeBrand', $groups['brands'][0]['title']);
        $this->assertSame('ShoeBrand Shoe', $groups['products'][0]['title']);

        $this->assertSame(route('categories.show', $category->getFullPath()), $groups['categories'][0]['url']);
        $this->assertSame(route('frontend.brands.show', $brand->id), $groups['brands'][0]['url']);

        $productUrl = $groups['products'][0]['url'];
        $this->assertTrue(
            Str::startsWith($productUrl, rtrim(config('app.url'), '/') . '/products/'),
            "Expected product URL to point to /products/*, got: {$productUrl}"
        );
    }

    public function test_submit_redirects_to_frontend_search_index(): void
    {
        Livewire::test(SearchMegaMenu::class)
            ->call('openDropdown')
            ->set('query', 'nike')
            ->call('submit')
            ->assertRedirect(route('frontend.search.index', ['q' => 'nike']));
    }

    public function test_go_active_redirects_to_first_match(): void
    {
        $category = Category::factory()->create([
            'name' => ['en' => 'Shoes'],
            'slug' => 'shoes',
            'is_active' => true,
        ]);

        $brand = BrandFactory::new()->withProfile('Nike')->create();

        $product = Product::factory()
            ->published()
            ->withBrand($brand)
            ->withAttributes(['name' => 'Nike Shoe'])
            ->create([
                'sku' => 'SHOE-999',
            ]);

        $product->categories()->syncWithoutDetaching([$category->id]);

        Livewire::test(SearchMegaMenu::class)
            ->call('openDropdown')
            ->set('query', 'shoe')
            ->call('refresh')
            ->call('setActiveIndex', 0)
            ->call('goActive')
            ->assertRedirect(route('categories.show', $category->getFullPath()));
    }
}


