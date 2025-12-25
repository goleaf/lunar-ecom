<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Product;
use App\Services\CollectionSchedulingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledCollectionsTest extends TestCase
{
    use RefreshDatabase;

    protected CollectionSchedulingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CollectionSchedulingService::class);
    }

    public function test_collection_can_be_scheduled_for_publish(): void
    {
        $collection = Collection::factory()->create();
        $publishAt = Carbon::now()->addDays(7);

        $collection->schedulePublish($publishAt);

        $this->assertTrue($collection->isScheduledForPublish());
        $this->assertEquals($publishAt->format('Y-m-d H:i:s'), $collection->scheduled_publish_at->format('Y-m-d H:i:s'));
    }

    public function test_collection_can_be_scheduled_for_unpublish(): void
    {
        $collection = Collection::factory()->create();
        $unpublishAt = Carbon::now()->addDays(14);

        $collection->scheduleUnpublish($unpublishAt);

        $this->assertTrue($collection->isScheduledForUnpublish());
        $this->assertEquals($unpublishAt->format('Y-m-d H:i:s'), $collection->scheduled_unpublish_at->format('Y-m-d H:i:s'));
    }

    public function test_collection_scheduled_scope_works(): void
    {
        $collection1 = Collection::factory()->create([
            'scheduled_publish_at' => Carbon::now()->addDays(7),
        ]);
        $collection2 = Collection::factory()->create([
            'scheduled_unpublish_at' => Carbon::now()->addDays(14),
        ]);
        $collection3 = Collection::factory()->create();

        $scheduled = Collection::scheduled()->get();

        $this->assertTrue($scheduled->contains($collection1));
        $this->assertTrue($scheduled->contains($collection2));
        $this->assertFalse($scheduled->contains($collection3));
    }

    public function test_collection_scheduled_for_publish_scope_works(): void
    {
        $collection1 = Collection::factory()->create([
            'scheduled_publish_at' => Carbon::now()->subMinute(), // Past date
        ]);
        $collection2 = Collection::factory()->create([
            'scheduled_publish_at' => Carbon::now()->addDays(7), // Future date
        ]);

        $readyToPublish = Collection::scheduledForPublish()->get();

        $this->assertTrue($readyToPublish->contains($collection1));
        $this->assertFalse($readyToPublish->contains($collection2));
    }

    public function test_collection_scheduling_service_validates_dates(): void
    {
        $collection = Collection::factory()->create();
        $publishAt = Carbon::now()->addDays(7);
        $unpublishAt = Carbon::now()->addDays(5); // Before publish date

        $this->expectException(\InvalidArgumentException::class);
        $this->service->validateScheduling($collection, $publishAt, $unpublishAt);
    }

    public function test_collection_scheduling_service_can_process_scheduled_publishes(): void
    {
        $collection = Collection::factory()->create([
            'scheduled_publish_at' => Carbon::now()->subMinute(),
            'auto_publish_products' => false,
        ]);

        $results = $this->service->processScheduledPublishes();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()['success']);
        $this->assertNull($collection->fresh()->scheduled_publish_at);
    }

    public function test_collection_auto_publishes_products_when_enabled(): void
    {
        $collection = Collection::factory()->create([
            'scheduled_publish_at' => Carbon::now()->subMinute(),
            'auto_publish_products' => true,
        ]);

        $product1 = Product::factory()->create(['status' => 'draft']);
        $product2 = Product::factory()->create(['status' => 'draft']);

        $collection->products()->attach([$product1->id, $product2->id]);

        $this->service->publishCollection($collection);

        $this->assertEquals('published', $product1->fresh()->status);
        $this->assertEquals('published', $product2->fresh()->status);
    }
}

