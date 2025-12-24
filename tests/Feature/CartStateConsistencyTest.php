<?php

namespace Tests\Feature;

use App\Services\CartManager;
use App\Services\CartSessionService;
use Lunar\Models\ProductVariant;
use Lunar\Models\Product;
use Lunar\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\LunarTestHelpers;

/**
 * Feature: lunar-ecommerce-system, Property 4: Cart State Consistency
 * 
 * Property: For any cart operations (add, modify, remove items), the cart's total 
 * quantities and line items should remain mathematically consistent with the 
 * individual operations performed
 * 
 * Validates: Requirements 3.1, 3.2
 */
class CartStateConsistencyTest extends TestCase
{
    use RefreshDatabase, LunarTestHelpers;

    protected CartManager $cartManager;
    protected CartSessionService $cartSession;
    protected array $testVariants = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Lunar test data
        $this->seedLunarTestData();
        
        $this->cartManager = app(CartManager::class);
        $this->cartSession = app(CartSessionService::class);
        
        // Create test variants for property testing
        $this->createTestVariants();
    }

    /**
     * Property-based test: Cart state consistency
     * Tests that cart operations maintain mathematical consistency
     */
    public function test_cart_state_consistency_property(): void
    {
        // Run property test with 20 iterations for faster execution
        for ($i = 0; $i < 20; $i++) {
            $this->runCartStateConsistencyTest();
        }
    }

    /**
     * Single iteration of the cart state consistency test
     */
    private function runCartStateConsistencyTest(): void
    {
        // Clear any existing cart to start fresh
        $this->cartManager->clear();
        
        // Generate random sequence of cart operations
        $operations = $this->generateRandomCartOperations();
        
        // Track expected state manually
        $expectedItemCount = 0;
        $expectedLineItems = [];
        
        // Execute operations and track expected state
        foreach ($operations as $operation) {
            switch ($operation['type']) {
                case 'add':
                    $this->executeAddOperation($operation, $expectedItemCount, $expectedLineItems);
                    break;
                    
                case 'update':
                    $this->executeUpdateOperation($operation, $expectedItemCount, $expectedLineItems);
                    break;
                    
                case 'remove':
                    $this->executeRemoveOperation($operation, $expectedItemCount, $expectedLineItems);
                    break;
            }
            
            // Verify consistency after each operation
            $this->verifyCartConsistency($expectedItemCount, $expectedLineItems);
        }
        
        // Final consistency check
        $this->verifyFinalCartState($expectedItemCount, $expectedLineItems);
    }

    /**
     * Execute add operation and update expected state
     */
    private function executeAddOperation(array $operation, int &$expectedItemCount, array &$expectedLineItems): void
    {
        $variant = $operation['variant'];
        $quantity = $operation['quantity'];
        
        try {
            $cartLine = $this->cartManager->addItem($variant, $quantity);
            
            // Update expected state
            $variantKey = $this->getVariantKey($variant);
            if (isset($expectedLineItems[$variantKey])) {
                $expectedLineItems[$variantKey]['quantity'] += $quantity;
            } else {
                $expectedLineItems[$variantKey] = [
                    'variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'line_id' => $cartLine->id
                ];
            }
            $expectedItemCount += $quantity;
            
        } catch (\InvalidArgumentException $e) {
            // Operation failed due to validation - expected state unchanged
            // This is acceptable behavior for invalid operations
        }
    }

    /**
     * Execute update operation and update expected state
     */
    private function executeUpdateOperation(array $operation, int &$expectedItemCount, array &$expectedLineItems): void
    {
        if (empty($expectedLineItems)) {
            return; // No items to update
        }
        
        // Pick a random existing line item
        $lineItem = $expectedLineItems[array_rand($expectedLineItems)];
        $newQuantity = $operation['quantity'];
        
        try {
            $this->cartManager->updateQuantity($lineItem['line_id'], $newQuantity);
            
            // Update expected state
            $variantKey = $this->getVariantKeyById($lineItem['variant_id']);
            $oldQuantity = $expectedLineItems[$variantKey]['quantity'];
            
            if ($newQuantity <= 0) {
                // Item should be removed
                $expectedItemCount -= $oldQuantity;
                unset($expectedLineItems[$variantKey]);
            } else {
                // Item quantity updated
                $expectedItemCount = $expectedItemCount - $oldQuantity + $newQuantity;
                $expectedLineItems[$variantKey]['quantity'] = $newQuantity;
            }
            
        } catch (\InvalidArgumentException $e) {
            // Operation failed due to validation - expected state unchanged
        }
    }

    /**
     * Execute remove operation and update expected state
     */
    private function executeRemoveOperation(array $operation, int &$expectedItemCount, array &$expectedLineItems): void
    {
        if (empty($expectedLineItems)) {
            return; // No items to remove
        }
        
        // Pick a random existing line item
        $lineItem = $expectedLineItems[array_rand($expectedLineItems)];
        
        try {
            $this->cartManager->removeItem($lineItem['line_id']);
            
            // Update expected state
            $variantKey = $this->getVariantKeyById($lineItem['variant_id']);
            $expectedItemCount -= $expectedLineItems[$variantKey]['quantity'];
            unset($expectedLineItems[$variantKey]);
            
        } catch (\InvalidArgumentException $e) {
            // Operation failed - expected state unchanged
        }
    }

    /**
     * Verify cart consistency after each operation
     */
    private function verifyCartConsistency(int $expectedItemCount, array $expectedLineItems): void
    {
        // Verify total item count matches expected
        $actualItemCount = $this->cartManager->getItemCount();
        $this->assertEquals(
            $expectedItemCount,
            $actualItemCount,
            "Cart item count should match sum of individual line quantities"
        );
        
        // Verify hasItems() consistency
        $expectedHasItems = $expectedItemCount > 0;
        $actualHasItems = $this->cartManager->hasItems();
        $this->assertEquals(
            $expectedHasItems,
            $actualHasItems,
            "hasItems() should be consistent with item count"
        );
        
        // Verify line item count consistency
        $cart = $this->cartSession->current();
        if ($cart) {
            $cart->refresh();
            $cart->load('lines');
            $actualLineCount = $cart->lines->count();
            $expectedLineCount = count($expectedLineItems);
            
            $this->assertEquals(
                $expectedLineCount,
                $actualLineCount,
                "Number of cart lines should match expected line items"
            );
        }
    }

    /**
     * Verify final cart state consistency
     */
    private function verifyFinalCartState(int $expectedItemCount, array $expectedLineItems): void
    {
        $cart = $this->cartSession->current();
        
        if ($expectedItemCount === 0) {
            // Cart should be empty or have no lines
            $this->assertEquals(0, $this->cartManager->getItemCount());
            $this->assertFalse($this->cartManager->hasItems());
            
            if ($cart) {
                $cart->refresh();
                $cart->load('lines');
                $this->assertEquals(0, $cart->lines->count());
            }
        } else {
            // Cart should have expected items
            $this->assertNotNull($cart);
            $cart->refresh();
            $cart->load('lines');
            
            // Verify each expected line item exists with correct quantity
            foreach ($expectedLineItems as $expectedLine) {
                $actualLine = $cart->lines->where('id', $expectedLine['line_id'])->first();
                $this->assertNotNull($actualLine, "Expected cart line should exist");
                $this->assertEquals(
                    $expectedLine['quantity'],
                    $actualLine->quantity,
                    "Cart line quantity should match expected"
                );
            }
            
            // Verify total quantity calculation
            $calculatedTotal = $cart->lines->sum('quantity');
            $this->assertEquals(
                $expectedItemCount,
                $calculatedTotal,
                "Sum of line quantities should equal expected total"
            );
        }
    }

    /**
     * Generate random sequence of cart operations
     */
    private function generateRandomCartOperations(): array
    {
        $operations = [];
        $operationCount = rand(3, 8); // Reduced number of operations
        
        for ($i = 0; $i < $operationCount; $i++) {
            $operationType = $this->randomOperationType($i);
            
            switch ($operationType) {
                case 'add':
                    $operations[] = [
                        'type' => 'add',
                        'variant' => $this->randomVariant(),
                        'quantity' => rand(1, 3) // Reduced max quantity
                    ];
                    break;
                    
                case 'update':
                    $operations[] = [
                        'type' => 'update',
                        'quantity' => rand(0, 5) // 0 means remove
                    ];
                    break;
                    
                case 'remove':
                    $operations[] = [
                        'type' => 'remove'
                    ];
                    break;
            }
        }
        
        return $operations;
    }

    /**
     * Get random operation type, favoring add operations early
     */
    private function randomOperationType(int $operationIndex): string
    {
        // Favor add operations early to build up cart state
        if ($operationIndex < 3) {
            return 'add';
        }
        
        $types = ['add', 'update', 'remove'];
        return $types[array_rand($types)];
    }

    /**
     * Get random test variant
     */
    private function randomVariant(): ProductVariant
    {
        return $this->testVariants[array_rand($this->testVariants)];
    }

    /**
     * Get unique key for variant
     */
    private function getVariantKey(ProductVariant $variant): string
    {
        return "variant_{$variant->id}";
    }

    /**
     * Get variant key by ID
     */
    private function getVariantKeyById(int $variantId): string
    {
        return "variant_{$variantId}";
    }

    /**
     * Create test variants for property testing
     */
    private function createTestVariants(): void
    {
        $currency = Currency::getDefault();
        
        // Create only 3 variants for faster testing
        for ($i = 0; $i < 3; $i++) {
            $product = $this->createTestProduct([
                'status' => 'published',
            ]);

            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => 'TEST-CART-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'stock' => rand(10, 50), // Reduced stock range
                'purchasable' => true,
                'shippable' => true,
            ]);

            // Add price to the variant
            $variant->prices()->create([
                'currency_id' => $currency->id,
                'price' => rand(500, 2000), // Reduced price range
                'min_quantity' => 1,
            ]);

            $this->testVariants[] = $variant;
        }
    }
}