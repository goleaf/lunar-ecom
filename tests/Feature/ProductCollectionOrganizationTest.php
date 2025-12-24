<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Product;
use App\Services\CollectionService;
use App\Services\CollectionGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\LunarTestHelpers;
use Lunar\Models\CollectionGroup;

/**
 * Feature: lunar-ecommerce-system, Property 3: Product Collection Organization
 * 
 * Property: For any product and collection assignment, the product should be 
 * retrievable through collection queries and maintain proper categorization relationships
 * 
 * Validates: Requirements 2.3
 */
class ProductCollectionOrganizationTest extends TestCase
{
    use RefreshDatabase, LunarTestHelpers;

    protected CollectionService $collectionService;
    protected CollectionGroupService $groupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collectionService = app(CollectionService::class);
        $this->groupService = app(CollectionGroupService::class);
        $this->seedLunarTestData();
    }

    /**
     * Property-based test: Product collection organization
     * Tests that products maintain proper relationships with collections
     */
    public function test_product_collection_organization_property(): void
    {
        // Run property test with 50 iterations to reduce complexity
        for ($i = 0; $i < 50; $i++) {
            $this->runCollectionOrganizationTest();
        }
    }

    /**
     * Single iteration of the collection organization test
     */
    private function runCollectionOrganizationTest(): void
    {
        // Use existing collections from seed data
        $collections = Collection::limit(2)->get();
        if ($collections->count() < 2) {
            $this->markTestSkipped('Not enough collections available for testing');
            return;
        }

        // Create test products
        $products = [];
        for ($i = 0; $i < rand(2, 4); $i++) {
            $products[] = $this->createTestProduct();
        }

        // Randomly assign products to collections
        $assignments = $this->generateRandomAssignments($products, $collections);
        $this->applyAssignments($assignments);

        // Test 1: Product-Collection Bidirectional Relationship
        $this->verifyBidirectionalRelationships($assignments);

        // Test 2: Collection Query Consistency
        $this->verifyCollectionQueryConsistency($collections);

        // Test 3: Product Retrieval Through Collections
        $this->verifyProductRetrievalThroughCollections($assignments);

        // Clean up
        foreach ($products as $product) {
            $product->delete();
        }
    }

    /**
     * Verify bidirectional relationships between products and collections
     */
    private function verifyBidirectionalRelationships(array $assignments): void
    {
        foreach ($assignments as $productId => $collectionIds) {
            $product = Product::find($productId);
            $product->load('collections');

            // Verify product knows about its collections
            $productCollectionIds = $product->collections->pluck('id')->toArray();
            sort($productCollectionIds);
            sort($collectionIds);

            $this->assertEquals(
                $collectionIds,
                $productCollectionIds,
                "Product {$productId} should be associated with collections " . implode(',', $collectionIds)
            );

            // Verify collections know about the product
            foreach ($collectionIds as $collectionId) {
                $collection = Collection::find($collectionId);
                $collection->load('products');
                
                $this->assertTrue(
                    $collection->products->contains('id', $productId),
                    "Collection {$collectionId} should contain product {$productId}"
                );
            }
        }
    }

    /**
     * Verify collection queries return consistent results
     */
    private function verifyCollectionQueryConsistency($collections): void
    {
        foreach ($collections as $collection) {
            // Test direct product retrieval through relationship
            $relationshipProducts = $collection->products()->get();
            
            // Verify we can count products consistently
            $directCount = $collection->products()->count();
            $relationshipCount = $relationshipProducts->count();

            $this->assertEquals(
                $directCount,
                $relationshipCount,
                "Collection {$collection->id} should return same product count through different query methods"
            );
        }
    }

    /**
     * Verify products can be retrieved through collection queries
     */
    private function verifyProductRetrievalThroughCollections(array $assignments): void
    {
        foreach ($assignments as $productId => $collectionIds) {
            $product = Product::find($productId);

            foreach ($collectionIds as $collectionId) {
                $collection = Collection::find($collectionId);
                $productsInCollection = $collection->products()->get();

                $this->assertTrue(
                    $productsInCollection->contains('id', $productId),
                    "Product {$productId} should be retrievable through collection {$collectionId}"
                );

                // Verify product data integrity when retrieved through collection
                $retrievedProduct = $productsInCollection->where('id', $productId)->first();
                $this->assertNotNull($retrievedProduct);
                $this->assertEquals($product->product_type_id, $retrievedProduct->product_type_id);
                $this->assertEquals($product->status, $retrievedProduct->status);
            }
        }
    }

    /**
     * Generate random product-collection assignments
     */
    private function generateRandomAssignments(array $products, $collections): array
    {
        $assignments = [];
        $collectionIds = $collections->pluck('id')->toArray();

        foreach ($products as $product) {
            // Each product gets assigned to 1-2 random collections
            $assignmentCount = rand(1, min(2, count($collectionIds)));
            $assignedCollections = array_rand(array_flip($collectionIds), $assignmentCount);
            
            // Ensure it's always an array
            if (!is_array($assignedCollections)) {
                $assignedCollections = [$assignedCollections];
            }

            $assignments[$product->id] = $assignedCollections;
        }

        return $assignments;
    }

    /**
     * Apply the assignments to the database
     */
    private function applyAssignments(array $assignments): void
    {
        foreach ($assignments as $productId => $collectionIds) {
            $product = Product::find($productId);
            $product->collections()->sync($collectionIds);
        }
    }
}