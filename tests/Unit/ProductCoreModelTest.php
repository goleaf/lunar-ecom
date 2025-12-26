<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Scout\ModelObserver;
use Lunar\Models\Tag;
use Tests\TestCase;

class ProductCoreModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(ModelObserver::class)) {
            ModelObserver::disableSyncingFor(Product::class);
        }
    }

    public function test_product_assigns_uuid_on_create(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->uuid);
        $this->assertTrue(Str::isUuid($product->uuid));
    }

    public function test_product_publish_sets_visibility_and_timestamp(): void
    {
        $product = Product::factory()->draft()->create([
            'published_at' => null,
        ]);

        $product->publish();

        $product->refresh();
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
        $this->assertSame(Product::VISIBILITY_PUBLIC, $product->visibility);
        $this->assertNotNull($product->published_at);
        $this->assertNull($product->scheduled_publish_at);
    }

    public function test_product_activate_sets_visibility_and_timestamp(): void
    {
        $product = Product::factory()->draft()->create([
            'published_at' => null,
        ]);

        $product->activate();

        $product->refresh();
        $this->assertSame(Product::STATUS_ACTIVE, $product->status);
        $this->assertSame(Product::VISIBILITY_PUBLIC, $product->visibility);
        $this->assertNotNull($product->published_at);
    }

    public function test_product_schedule_publish_sets_visibility_and_dates(): void
    {
        $product = Product::factory()->draft()->create();
        $publishAt = now()->addDays(2);

        $product->schedulePublish($publishAt);

        $product->refresh();
        $this->assertSame(Product::VISIBILITY_SCHEDULED, $product->visibility);
        $this->assertNotNull($product->scheduled_publish_at);
    }

    public function test_product_lock_prevents_updates(): void
    {
        $product = Product::factory()->create();
        $product->lock('Live orders');

        $this->expectException(ValidationException::class);
        $product->update(['short_description' => 'Updated while locked']);
    }

    public function test_product_duplicate_resets_status_and_clones_relations(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $product = Product::factory()->active()->create([
            'visibility' => Product::VISIBILITY_PUBLIC,
        ]);
        $product->categories()->sync([$category->id]);
        $product->tags()->sync([$tag->id]);

        $clone = $product->duplicate('Clone Name');

        $this->assertNotSame($product->id, $clone->id);
        $this->assertNotSame($product->uuid, $clone->uuid);
        $this->assertSame(Product::STATUS_DRAFT, $clone->status);
        $this->assertSame(Product::VISIBILITY_PRIVATE, $clone->visibility);
        $this->assertNull($clone->published_at);
        $this->assertCount(1, $clone->categories);
        $this->assertCount(1, $clone->tags);
    }

    public function test_product_version_create_and_restore(): void
    {
        $product = Product::factory()->create([
            'short_description' => 'Original description',
        ]);

        $version = $product->createVersion('v1', 'Initial snapshot');

        $product->update(['short_description' => 'Updated description']);

        $product->restoreVersion($version);

        $product->refresh();
        $this->assertSame('Original description', $product->short_description);
        $this->assertSame($version->id, $product->parent_version_id);
        $this->assertGreaterThanOrEqual(1, $product->versions()->count());
    }
}
