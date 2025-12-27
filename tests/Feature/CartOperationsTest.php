<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\LunarTestHelpers;
use App\Services\CartManager;
use App\Services\CartSessionService;
use App\Models\ProductVariant;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CartOperationsTest extends TestCase
{
    use RefreshDatabase, LunarTestHelpers;

    protected CartManager $cartManager;
    protected CartSessionService $cartSession;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Lunar test data
        $this->seedLunarTestData();
        
        $this->cartManager = app(CartManager::class);
        $this->cartSession = app(CartSessionService::class);
        
        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create product
        $product = $this->createTestProduct([
            'status' => 'published',
        ]);

        // Create product variant
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-001',
            'status' => 'active',
            'enabled' => true,
            'stock' => 10,
            'purchasable' => 'always',
            'shippable' => true,
        ]);

        // Add price to the variant
        $currency = \Lunar\Models\Currency::getDefault();
        $this->variant->prices()->create([
            'currency_id' => $currency->id,
            'price' => 1000, // $10.00 in cents
            'min_quantity' => 1,
        ]);
    }

    #[Test]
    public function it_can_add_items_to_cart()
    {
        $cartLine = $this->cartManager->addItem($this->variant, 2);
        
        $this->assertNotNull($cartLine);
        $this->assertEquals(2, $cartLine->quantity);
        $this->assertEquals(2, $this->cartManager->getItemCount());
        $this->assertTrue($this->cartManager->hasItems());
    }

    #[Test]
    public function it_can_update_cart_line_quantity()
    {
        $cartLine = $this->cartManager->addItem($this->variant, 2);
        
        $this->cartManager->updateQuantity($cartLine->id, 5);
        
        $cartLine->refresh();
        $this->assertEquals(5, $cartLine->quantity);
        $this->assertEquals(5, $this->cartManager->getItemCount());
    }

    #[Test]
    public function it_can_remove_items_from_cart()
    {
        $cartLine = $this->cartManager->addItem($this->variant, 2);
        
        $this->cartManager->removeItem($cartLine->id);
        
        $this->assertEquals(0, $this->cartManager->getItemCount());
        $this->assertFalse($this->cartManager->hasItems());
    }

    #[Test]
    public function it_can_clear_cart()
    {
        $this->cartManager->addItem($this->variant, 2);
        $this->assertEquals(2, $this->cartManager->getItemCount());
        
        $this->cartManager->clear();
        
        $this->assertEquals(0, $this->cartManager->getItemCount());
        $this->assertFalse($this->cartManager->hasItems());
    }

    #[Test]
    public function it_validates_quantity_when_adding_items()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');
        
        $this->cartManager->addItem($this->variant, 0);
    }

    #[Test]
    public function it_validates_stock_availability()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');
        
        $this->cartManager->addItem($this->variant, 15); // More than available stock (10)
    }

    #[Test]
    public function it_validates_purchasable_items()
    {
        $this->variant->update(['purchasable' => 'never']);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item is not purchasable');
        
        $this->cartManager->addItem($this->variant, 1);
    }

    #[Test]
    public function it_handles_existing_cart_lines_correctly()
    {
        // Add item first time
        $cartLine1 = $this->cartManager->addItem($this->variant, 2);
        
        // Add same item again
        $cartLine2 = $this->cartManager->addItem($this->variant, 3);
        
        // Should be the same cart line with updated quantity
        $this->assertEquals($cartLine1->id, $cartLine2->id);
        $this->assertEquals(5, $cartLine2->quantity);
        $this->assertEquals(5, $this->cartManager->getItemCount());
    }

    #[Test]
    public function it_throws_exception_for_invalid_cart_line()
    {
        // First create a cart by adding an item
        $this->cartManager->addItem($this->variant, 1);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cart line not found');
        
        $this->cartManager->removeItem(999); // Non-existent cart line ID
    }

    #[Test]
    public function it_throws_exception_when_updating_invalid_cart_line()
    {
        // First create a cart by adding an item
        $this->cartManager->addItem($this->variant, 1);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cart line not found');
        
        $this->cartManager->updateQuantity(999, 5); // Non-existent cart line ID
    }

    #[Test]
    public function it_removes_item_when_quantity_is_zero()
    {
        $cartLine = $this->cartManager->addItem($this->variant, 2);
        
        $this->cartManager->updateQuantity($cartLine->id, 0);
        
        $this->assertEquals(0, $this->cartManager->getItemCount());
        $this->assertFalse($this->cartManager->hasItems());
    }
}