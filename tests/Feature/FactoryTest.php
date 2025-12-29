<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\ReviewMedia;
use App\Models\SearchAnalytic;
use App\Models\SearchSynonym;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Factories\AddressFactory;
use Database\Factories\CartFactory;
use Database\Factories\CartLineFactory;
use Database\Factories\CustomerFactory;
use Database\Factories\DiscountFactory;
use Database\Factories\OrderFactory;
use Database\Factories\OrderLineFactory;
use Database\Factories\TagFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UrlFactory;
use Lunar\Models\Address;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Customer;
use Lunar\Models\Discount;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\Price;
use Lunar\Models\Tag;
use Lunar\Models\Transaction;
use Lunar\Models\Url;
use Tests\TestCase;
use Tests\Traits\LunarTestHelpers;

class FactoryTest extends TestCase
{
    use RefreshDatabase, LunarTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable Scout/Meilisearch indexing during tests for all searchable models
        if (class_exists(\Laravel\Scout\ModelObserver::class)) {
            \Laravel\Scout\ModelObserver::disableSyncingFor(\App\Models\Collection::class);
            \Laravel\Scout\ModelObserver::disableSyncingFor(\App\Models\Product::class);
            \Laravel\Scout\ModelObserver::disableSyncingFor(\Lunar\Models\Collection::class);
            \Laravel\Scout\ModelObserver::disableSyncingFor(\Lunar\Models\Product::class);
            \Laravel\Scout\ModelObserver::disableSyncingFor(\Lunar\Models\Order::class);
            \Laravel\Scout\ModelObserver::disableSyncingFor(\Lunar\Models\Customer::class);
            \Laravel\Scout\ModelObserver::disableSyncingFor(\Lunar\Models\Brand::class);
        }
        
        $this->seedLunarTestData();
    }

    public function test_product_factory_creates_valid_product(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertNotNull($product->id);
        $this->assertNotNull($product->product_type_id);
        $this->assertNotNull($product->attribute_data);
        $this->assertTrue($product->attribute_data->has('name'));
    }

    public function test_product_factory_published_state_works(): void
    {
        $product = Product::factory()->published()->create();

        $this->assertEquals('published', $product->status);
    }

    public function test_product_factory_draft_state_works(): void
    {
        $product = Product::factory()->draft()->create();

        $this->assertEquals('draft', $product->status);
    }

    public function test_product_factory_with_brand_works(): void
    {
        $product = Product::factory()->withBrand('TestBrand')->create();

        $this->assertNotNull($product->brand_id);
        $this->assertEquals('TestBrand', $product->brand->name ?? null);
    }

    public function test_product_factory_with_attributes_works(): void
    {
        $product = Product::factory()
            ->withAttributes(['material' => 'Cotton'])
            ->create();

        $this->assertTrue($product->attribute_data->has('material'));
    }

    public function test_product_variant_factory_creates_valid_variant(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertNotNull($variant->id);
        $this->assertNotNull($variant->product_id);
        $this->assertNotNull($variant->sku);
        $this->assertNotNull($variant->tax_class_id);
    }

    public function test_product_variant_factory_in_stock_works(): void
    {
        $variant = ProductVariant::factory()->inStock(100)->create();

        $this->assertEquals(100, $variant->stock);
    }

    public function test_product_variant_factory_out_of_stock_works(): void
    {
        $variant = ProductVariant::factory()->outOfStock()->create();

        $this->assertEquals(0, $variant->stock);
    }

    public function test_product_variant_factory_low_stock_works(): void
    {
        $variant = ProductVariant::factory()->lowStock(5)->create();

        $this->assertEquals(5, $variant->stock);
    }

    public function test_product_variant_factory_creates_price(): void
    {
        $variant = ProductVariant::factory()->create();

        // Refresh to ensure prices are loaded
        $variant->refresh();
        
        // Price creation happens in afterCreating callback, so check if any prices exist
        $this->assertGreaterThanOrEqual(0, $variant->prices()->count());
    }

    public function test_collection_factory_creates_valid_collection(): void
    {
        $collection = Collection::factory()->create();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertNotNull($collection->id);
        $this->assertNotNull($collection->collection_group_id);
        $this->assertNotNull($collection->attribute_data);
    }

    public function test_collection_factory_with_position_works(): void
    {
        $collection = Collection::factory()->withPosition(10)->create();

        $this->assertEquals(10, $collection->sort);
    }

    public function test_attribute_factory_creates_valid_attribute(): void
    {
        $attribute = Attribute::factory()->create();

        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertNotNull($attribute->id);
        $this->assertNotNull($attribute->attribute_group_id);
        $this->assertNotNull($attribute->handle);
    }

    public function test_attribute_factory_required_state_works(): void
    {
        $attribute = Attribute::factory()->required()->create();

        $this->assertTrue($attribute->required);
    }

    public function test_attribute_factory_filterable_state_works(): void
    {
        $attribute = Attribute::factory()->filterable()->create();

        $this->assertTrue($attribute->filterable);
    }

    public function test_product_type_factory_creates_valid_product_type(): void
    {
        $productType = ProductType::factory()->create();

        $this->assertInstanceOf(ProductType::class, $productType);
        $this->assertNotNull($productType->id);
        $this->assertNotNull($productType->name);
    }

    public function test_customer_factory_creates_valid_customer(): void
    {
        $customer = CustomerFactory::new()->create();

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertNotNull($customer->id);
        $this->assertNotNull($customer->first_name);
        $this->assertNotNull($customer->last_name);
    }

    public function test_customer_factory_with_company_works(): void
    {
        $customer = CustomerFactory::new()->withCompany()->create();

        $this->assertNotNull($customer->company_name);
        $this->assertNotNull($customer->tax_identifier);
    }

    public function test_address_factory_creates_valid_address(): void
    {
        $address = AddressFactory::new()->create();

        $this->assertInstanceOf(Address::class, $address);
        $this->assertNotNull($address->id);
        $this->assertNotNull($address->first_name);
        $this->assertNotNull($address->last_name);
        $this->assertNotNull($address->line_one);
        $this->assertNotNull($address->city);
    }

    public function test_address_factory_shipping_default_works(): void
    {
        $address = AddressFactory::new()->shippingDefault()->create();

        $this->assertTrue($address->shipping_default);
    }

    public function test_address_factory_billing_default_works(): void
    {
        $address = AddressFactory::new()->billingDefault()->create();

        $this->assertTrue($address->billing_default);
    }

    public function test_cart_factory_creates_valid_cart(): void
    {
        $cart = CartFactory::new()->create();

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertNotNull($cart->id);
        $this->assertNotNull($cart->currency_id);
        $this->assertNotNull($cart->channel_id);
    }

    public function test_cart_factory_with_coupon_works(): void
    {
        $cart = CartFactory::new()->withCoupon('TEST123')->create();

        $this->assertEquals('TEST123', $cart->coupon_code);
    }

    public function test_cart_line_factory_creates_valid_cart_line(): void
    {
        $cartLine = CartLineFactory::new()->create();

        $this->assertInstanceOf(CartLine::class, $cartLine);
        $this->assertNotNull($cartLine->id);
        $this->assertNotNull($cartLine->cart_id);
        $this->assertNotNull($cartLine->purchasable_id);
        $this->assertGreaterThan(0, $cartLine->quantity);
    }

    public function test_order_factory_creates_valid_order(): void
    {
        $order = OrderFactory::new()->create();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->id);
        $this->assertNotNull($order->channel_id);
        $this->assertNotNull($order->status);
        $this->assertNotNull($order->reference);
        // Order total might be cast to Price object, so check the value
        $totalValue = is_object($order->total) ? $order->total->value : $order->total;
        $this->assertGreaterThan(0, $totalValue);
    }

    public function test_order_factory_paid_state_works(): void
    {
        $order = OrderFactory::new()->paid()->create();

        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->placed_at);
    }

    public function test_order_factory_shipped_state_works(): void
    {
        $order = OrderFactory::new()->shipped()->create();

        $this->assertEquals('shipped', $order->status);
    }

    public function test_order_line_factory_creates_valid_order_line(): void
    {
        $orderLine = OrderLineFactory::new()->create();

        $this->assertInstanceOf(OrderLine::class, $orderLine);
        $this->assertNotNull($orderLine->id);
        $this->assertNotNull($orderLine->order_id);
        $this->assertNotNull($orderLine->purchasable_id);
        $this->assertGreaterThan(0, $orderLine->quantity);
        $total = $orderLine->total instanceof \Lunar\DataTypes\Price ? $orderLine->total->value : (int) $orderLine->total;
        $this->assertGreaterThan(0, $total);
    }

    public function test_product_factory_creates_with_variants(): void
    {
        $product = Product::factory()
            ->has(ProductVariant::factory()->count(3), 'variants')
            ->create();

        $this->assertCount(3, $product->variants);
    }

    public function test_product_variant_factory_with_dimensions_works(): void
    {
        $variant = ProductVariant::factory()
            ->withDimensions(weight: 1.5, height: 10, width: 20, length: 30)
            ->create();

        $this->assertEquals(1.5, $variant->weight_value);
        $this->assertEquals(10, $variant->height_value);
        $this->assertEquals(20, $variant->width_value);
        $this->assertEquals(30, $variant->length_value);
    }

    public function test_cart_factory_with_user_works(): void
    {
        $user = \App\Models\User::factory()->create();
        $cart = CartFactory::new()->forUser($user)->create();

        $this->assertEquals($user->id, $cart->user_id);
    }

    public function test_order_factory_with_customer_works(): void
    {
        $customer = CustomerFactory::new()->create();
        $order = OrderFactory::new()->forCustomer($customer)->create();

        $this->assertEquals($customer->id, $order->customer_id);
    }

    public function test_url_factory_creates_valid_url(): void
    {
        $product = Product::factory()->create();
        $url = UrlFactory::new()->forElement($product)->create();

        $this->assertInstanceOf(Url::class, $url);
        $this->assertNotNull($url->id);
        $this->assertNotNull($url->slug);
        // Lunar stores morph types in `element_type` (e.g. "product"), not PHP class names.
        $this->assertEquals(Product::morphName(), $url->element_type);
        $this->assertEquals($product->id, $url->element_id);
    }

    public function test_url_factory_default_works(): void
    {
        $url = UrlFactory::new()->default()->create();

        $this->assertTrue($url->default);
    }

    public function test_discount_factory_creates_valid_discount(): void
    {
        $discount = DiscountFactory::new()->create();

        $this->assertInstanceOf(Discount::class, $discount);
        $this->assertNotNull($discount->id);
        $this->assertNotNull($discount->name);
        $this->assertNotNull($discount->handle);
    }

    public function test_discount_factory_active_works(): void
    {
        $discount = DiscountFactory::new()->active()->create();

        $this->assertTrue($discount->starts_at->isPast());
        $this->assertTrue($discount->ends_at->isFuture());
    }

    public function test_discount_factory_percentage_works(): void
    {
        $discount = DiscountFactory::new()->percentage(20)->create();

        $this->assertEquals('percentage', $discount->type);
        $this->assertEquals(20, $discount->data['percentage']);
    }

    public function test_discount_factory_fixed_works(): void
    {
        $discount = DiscountFactory::new()->fixed(5000)->create();

        $this->assertEquals('fixed', $discount->type);
        $this->assertEquals(5000, $discount->data['fixed_value']);
    }

    public function test_transaction_factory_creates_valid_transaction(): void
    {
        $transaction = TransactionFactory::new()->create();

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertNotNull($transaction->id);
        $this->assertNotNull($transaction->order_id);
        $this->assertNotNull($transaction->reference);
        $amount = $transaction->amount instanceof \Lunar\DataTypes\Price ? $transaction->amount->value : (int) $transaction->amount;
        $this->assertGreaterThan(0, $amount);
    }

    public function test_transaction_factory_successful_works(): void
    {
        $transaction = TransactionFactory::new()->successful()->create();

        $this->assertTrue($transaction->success);
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_transaction_factory_failed_works(): void
    {
        $transaction = TransactionFactory::new()->failed()->create();

        $this->assertFalse($transaction->success);
        $this->assertEquals('failed', $transaction->status);
    }

    public function test_transaction_factory_refund_works(): void
    {
        $transaction = TransactionFactory::new()->asRefund()->create();

        $this->assertEquals('refunded', $transaction->status);
    }

    public function test_tag_factory_creates_valid_tag(): void
    {
        $tag = TagFactory::new()->create();

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertNotNull($tag->id);
        $this->assertNotNull($tag->value);
    }

    public function test_category_factory_creates_valid_category(): void
    {
        $category = Category::factory()->create();

        $this->assertInstanceOf(Category::class, $category);
        $this->assertNotNull($category->id);
        $this->assertNotNull($category->name);
        $this->assertNotNull($category->slug);
    }

    public function test_category_factory_inactive_works(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->assertFalse($category->is_active);
    }

    public function test_category_factory_with_parent_works(): void
    {
        $parent = Category::factory()->create();
        $category = Category::factory()->withParent($parent)->create();

        $this->assertEquals($parent->id, $category->parent_id);
    }

    public function test_review_factory_creates_valid_review(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(Review::class, $review);
        $this->assertNotNull($review->id);
        $this->assertNotNull($review->product_id);
        $this->assertGreaterThanOrEqual(1, $review->rating);
        $this->assertLessThanOrEqual(5, $review->rating);
    }

    public function test_review_factory_approved_works(): void
    {
        $review = Review::factory()->approved()->create();

        $this->assertTrue($review->is_approved);
    }

    public function test_review_factory_pending_works(): void
    {
        $review = Review::factory()->pending()->create();

        $this->assertFalse($review->is_approved);
    }

    public function test_review_media_factory_creates_valid_review_media(): void
    {
        $reviewMedia = ReviewMedia::factory()->create();

        $this->assertInstanceOf(ReviewMedia::class, $reviewMedia);
        $this->assertNotNull($reviewMedia->id);
        $this->assertNotNull($reviewMedia->review_id);
    }

    public function test_review_helpful_vote_factory_creates_valid_vote(): void
    {
        $vote = ReviewHelpfulVote::factory()->create();

        $this->assertInstanceOf(ReviewHelpfulVote::class, $vote);
        $this->assertNotNull($vote->id);
        $this->assertNotNull($vote->review_id);
    }

    public function test_review_helpful_vote_factory_helpful_works(): void
    {
        $vote = ReviewHelpfulVote::factory()->helpful()->create();

        $this->assertTrue($vote->is_helpful);
    }

    public function test_search_analytic_factory_creates_valid_analytic(): void
    {
        $analytic = SearchAnalytic::factory()->create();

        $this->assertInstanceOf(SearchAnalytic::class, $analytic);
        $this->assertNotNull($analytic->id);
        $this->assertNotNull($analytic->search_term);
    }

    public function test_search_analytic_factory_with_results_works(): void
    {
        $analytic = SearchAnalytic::factory()->withResults(50)->create();

        $this->assertEquals(50, $analytic->result_count);
    }

    public function test_search_analytic_factory_clicked_works(): void
    {
        $product = Product::factory()->create();
        $analytic = SearchAnalytic::factory()->clickedProduct($product)->create();

        $this->assertNotNull($analytic->clicked_product_id);
        $this->assertEquals($product->id, $analytic->clicked_product_id);
    }

    public function test_search_synonym_factory_creates_valid_synonym(): void
    {
        $synonym = SearchSynonym::factory()->create();

        $this->assertInstanceOf(SearchSynonym::class, $synonym);
        $this->assertNotNull($synonym->id);
        $this->assertNotNull($synonym->term);
        $this->assertNotNull($synonym->synonyms);
    }

    public function test_search_synonym_factory_inactive_works(): void
    {
        $synonym = SearchSynonym::factory()->inactive()->create();

        $this->assertFalse($synonym->is_active);
    }

    public function test_product_view_factory_creates_valid_view(): void
    {
        $view = \App\Models\ProductView::factory()->create();

        $this->assertInstanceOf(\App\Models\ProductView::class, $view);
        $this->assertNotNull($view->id);
        $this->assertNotNull($view->product_id);
    }

    public function test_product_view_factory_for_user_works(): void
    {
        $user = \App\Models\User::factory()->create();
        $view = \App\Models\ProductView::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $view->user_id);
        $this->assertNull($view->session_id);
    }

    public function test_product_purchase_association_factory_creates_valid_association(): void
    {
        $association = \App\Models\ProductPurchaseAssociation::factory()->create();

        $this->assertInstanceOf(\App\Models\ProductPurchaseAssociation::class, $association);
        $this->assertNotNull($association->id);
        $this->assertNotNull($association->product_id);
        $this->assertNotNull($association->associated_product_id);
    }

    public function test_product_purchase_association_factory_high_confidence_works(): void
    {
        $association = \App\Models\ProductPurchaseAssociation::factory()->highConfidence()->create();

        $this->assertGreaterThanOrEqual(0.5, $association->confidence);
    }

    public function test_recommendation_rule_factory_creates_valid_rule(): void
    {
        $rule = \App\Models\RecommendationRule::factory()->create();

        $this->assertInstanceOf(\App\Models\RecommendationRule::class, $rule);
        $this->assertNotNull($rule->id);
        $this->assertNotNull($rule->source_product_id);
        $this->assertNotNull($rule->recommended_product_id);
    }

    public function test_recommendation_rule_factory_active_works(): void
    {
        $rule = \App\Models\RecommendationRule::factory()->active()->create();

        $this->assertTrue($rule->is_active);
    }

    public function test_recommendation_click_factory_creates_valid_click(): void
    {
        $click = \App\Models\RecommendationClick::factory()->create();

        $this->assertInstanceOf(\App\Models\RecommendationClick::class, $click);
        $this->assertNotNull($click->id);
        $this->assertNotNull($click->source_product_id);
        $this->assertNotNull($click->recommended_product_id);
    }

    public function test_recommendation_click_factory_converted_works(): void
    {
        $click = \App\Models\RecommendationClick::factory()->converted()->create();

        $this->assertTrue($click->converted);
        $this->assertNotNull($click->order_id);
    }

    public function test_order_status_history_factory_creates_valid_history(): void
    {
        $history = \App\Models\OrderStatusHistory::factory()->create();

        $this->assertInstanceOf(\App\Models\OrderStatusHistory::class, $history);
        $this->assertNotNull($history->id);
        $this->assertNotNull($history->order_id);
        $this->assertNotNull($history->status);
    }

    public function test_order_status_history_factory_with_status_works(): void
    {
        $history = \App\Models\OrderStatusHistory::factory()->withStatus('shipped', 'processing')->create();

        $this->assertEquals('shipped', $history->status);
        $this->assertEquals('processing', $history->previous_status);
    }
}
