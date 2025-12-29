<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Frontend\ProductIndex;
use App\Livewire\Frontend\ProductShow;
use App\Models\Product;
use Laravel\Scout\ModelObserver;
use Livewire\Livewire;
use Lunar\Models\Url;
use Tests\TestCase;

class ProductLivewireTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(ModelObserver::class)) {
            ModelObserver::disableSyncingFor(Product::class);
        }
    }

    public function test_product_index_lists_published_and_active_products(): void
    {
        $activeProduct = Product::factory()->active()->create();
        $draftProduct = Product::factory()->draft()->create();

        Livewire::test(ProductIndex::class)
            ->assertSee($activeProduct->translateAttribute('name'))
            ->assertDontSee($draftProduct->translateAttribute('name'));
    }

    public function test_product_show_renders_for_published_product(): void
    {
        $product = Product::factory()->active()->create();
        // Lunar stores morph types (e.g. "product") in element_type.
        $url = Url::where('element_type', Product::morphName())
            ->where('element_id', $product->id)
            ->firstOrFail();

        Livewire::test(ProductShow::class, ['slug' => $url->slug])
            ->assertSee($product->translateAttribute('name'));
    }
}
