<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Address;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\Price;
use Tests\TestCase;
use Tests\Traits\LunarTestHelpers;

class FactoryTest extends TestCase
{
    use RefreshDatabase, LunarTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLunarTestData();
    }

    /** @test */
    public function product_factory_creates_valid_product(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertNotNull($product->id);
        $this->assertNotNull($product->product_type_id);
        $this->assertNotNull($product->attribute_data);
        $this->assertTrue($product->attribute_data->has('name'));
    }

    /** @test */
    public function product_factory_published_state_works(): void
    {
        $product = Product::factory()->published()->create();

        $this->assertEquals('published', $product->status);
    }

    /** @test */
    public function product_factory_draft_state_works(): void
    {
        $product = Product::factory()->draft()->create();

        $this->assertEquals('draft', $product->status);
    }

    /** @test */
    public function product_factory_with_brand_works(): void
    {
        $product = Product::factory()->withBrand('TestBrand')->create();

        $this->assertEquals('TestBrand', $product->brand);
    }

    /** @test */
    public function product_factory_with_attributes_works(): void
    {
        $product = Product::factory()
            ->withAttributes(['material' => 'Cotton'])
            ->create();

        $this->assertTrue($product->attribute_data->has('material'));
    }

    /** @test */
    public function product_variant_factory_creates_valid_variant(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertNotNull($variant->id);
        $this->assertNotNull($variant->product_id);
        $this->assertNotNull($variant->sku);
        $this->assertNotNull($variant->tax_class_id);
    }

    /** @test */
    public function product_variant_factory_in_stock_works(): void
    {
        $variant = ProductVariant::factory()->inStock(100)->create();

        $this->assertEquals(100, $variant->stock);
    }

    /** @test */
    public function product_variant_factory_out_of_stock_works(): void
    {
        $variant = ProductVariant::factory()->outOfStock()->create();

        $this->assertEquals(0, $variant->stock);
    }

    /** @test */
    public function product_variant_factory_low_stock_works(): void
    {
        $variant = ProductVariant::factory()->lowStock(5)->create();

        $this->assertEquals(5, $variant->stock);
    }

    /** @test */
    public function product_variant_factory_creates_price(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->assertTrue($variant->prices()->exists());
    }

    /** @test */
    public function collection_factory_creates_valid_collection(): void
    {
        $collection = Collection::factory()->create();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertNotNull($collection->id);
        $this->assertNotNull($collection->collection_group_id);
        $this->assertNotNull($collection->attribute_data);
    }

    /** @test */
    public function collection_factory_with_position_works(): void
    {
        $collection = Collection::factory()->withPosition(10)->create();

        $this->assertEquals(10, $collection->sort);
    }

    /** @test */
    public function attribute_factory_creates_valid_attribute(): void
    {
        $attribute = Attribute::factory()->create();

        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertNotNull($attribute->id);
        $this->assertNotNull($attribute->attribute_group_id);
        $this->assertNotNull($attribute->handle);
    }

    /** @test */
    public function attribute_factory_required_state_works(): void
    {
        $attribute = Attribute::factory()->required()->create();

        $this->assertTrue($attribute->required);
    }

    /** @test */
    public function attribute_factory_filterable_state_works(): void
    {
        $attribute = Attribute::factory()->filterable()->create();

        $this->assertTrue($attribute->filterable);
    }

    /** @test */
    public function product_type_factory_creates_valid_product_type(): void
    {
        $productType = ProductType::factory()->create();

        $this->assertInstanceOf(ProductType::class, $productType);
        $this->assertNotNull($productType->id);
        $this->assertNotNull($productType->name);
    }

    /** @test */
    public function customer_factory_creates_valid_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertNotNull($customer->id);
        $this->assertNotNull($customer->first_name);
        $this->assertNotNull($customer->last_name);
    }

    /** @test */
    public function customer_factory_with_company_works(): void
    {
        $customer = Customer::factory()->withCompany()->create();

        $this->assertNotNull($customer->company_name);
        $this->assertNotNull($customer->vat_no);
    }

    /** @test */
    public function address_factory_creates_valid_address(): void
    {
        $address = Address::factory()->create();

        $this->assertInstanceOf(Address::class, $address);
        $this->assertNotNull($address->id);
        $this->assertNotNull($address->first_name);
        $this->assertNotNull($address->last_name);
        $this->assertNotNull($address->line_one);
        $this->assertNotNull($address->city);
    }

    /** @test */
    public function address_factory_shipping_default_works(): void
    {
        $address = Address::factory()->shippingDefault()->create();

        $this->assertTrue($address->shipping_default);
    }

    /** @test */
    public function address_factory_billing_default_works(): void
    {
        $address = Address::factory()->billingDefault()->create();

        $this->assertTrue($address->billing_default);
    }

    /** @test */
    public function cart_factory_creates_valid_cart(): void
    {
        $cart = Cart::factory()->create();

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertNotNull($cart->id);
        $this->assertNotNull($cart->currency_id);
        $this->assertNotNull($cart->channel_id);
    }

    /** @test */
    public function cart_factory_with_coupon_works(): void
    {
        $cart = Cart::factory()->withCoupon('TEST123')->create();

        $this->assertEquals('TEST123', $cart->coupon_code);
    }

    /** @test */
    public function cart_line_factory_creates_valid_cart_line(): void
    {
        $cartLine = CartLine::factory()->create();

        $this->assertInstanceOf(CartLine::class, $cartLine);
        $this->assertNotNull($cartLine->id);
        $this->assertNotNull($cartLine->cart_id);
        $this->assertNotNull($cartLine->purchasable_id);
        $this->assertGreaterThan(0, $cartLine->quantity);
    }

    /** @test */
    public function order_factory_creates_valid_order(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->id);
        $this->assertNotNull($order->channel_id);
        $this->assertNotNull($order->status);
        $this->assertNotNull($order->reference);
        $this->assertGreaterThan(0, $order->total);
    }

    /** @test */
    public function order_factory_paid_state_works(): void
    {
        $order = Order::factory()->paid()->create();

        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->placed_at);
    }

    /** @test */
    public function order_factory_shipped_state_works(): void
    {
        $order = Order::factory()->shipped()->create();

        $this->assertEquals('shipped', $order->status);
    }

    /** @test */
    public function order_line_factory_creates_valid_order_line(): void
    {
        $orderLine = OrderLine::factory()->create();

        $this->assertInstanceOf(OrderLine::class, $orderLine);
        $this->assertNotNull($orderLine->id);
        $this->assertNotNull($orderLine->order_id);
        $this->assertNotNull($orderLine->purchasable_id);
        $this->assertGreaterThan(0, $orderLine->quantity);
        $this->assertGreaterThan(0, $orderLine->total);
    }

    /** @test */
    public function product_factory_creates_with_variants(): void
    {
        $product = Product::factory()
            ->has(ProductVariant::factory()->count(3), 'variants')
            ->create();

        $this->assertCount(3, $product->variants);
    }

    /** @test */
    public function product_variant_factory_with_dimensions_works(): void
    {
        $variant = ProductVariant::factory()
            ->withDimensions(weight: 1.5, height: 10, width: 20, length: 30)
            ->create();

        $this->assertEquals(1.5, $variant->weight_value);
        $this->assertEquals(10, $variant->height_value);
        $this->assertEquals(20, $variant->width_value);
        $this->assertEquals(30, $variant->length_value);
    }

    /** @test */
    public function cart_factory_with_user_works(): void
    {
        $user = \App\Models\User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $cart->user_id);
    }

    /** @test */
    public function order_factory_with_customer_works(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->forCustomer($customer)->create();

        $this->assertEquals($customer->id, $order->customer_id);
    }
}

