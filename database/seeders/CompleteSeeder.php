<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\SearchAnalytic;
use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;
use Database\Factories\DiscountFactory;
use Database\Factories\TagFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UrlFactory;
use Lunar\Models\Address;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Channel;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Discount;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\Price;
use Lunar\Models\Tag;
use Lunar\Models\TaxClass;
use Lunar\Models\Transaction;
use Lunar\Models\Url;
use App\Models\User;

/**
 * Complete seeder that creates a full e-commerce catalog with:
 * - Products, variants, collections, attributes
 * - Customers, addresses
 * - Carts with cart lines
 * - Orders with order lines
 * 
 * This is the maximum comprehensive seeder.
 */
class CompleteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting complete database seeding...');

        // Step 1: Setup Lunar essentials
        $this->command->info('📦 Setting up Lunar essentials...');
        $channel = $this->getOrCreateChannel();
        $currency = $this->getOrCreateCurrency();
        $language = $this->getOrCreateLanguage();
        $customerGroups = CustomerGroupSeeder::seed();
        $customerGroup = $customerGroups[CustomerGroupSeeder::DEFAULT_HANDLE] ?? CustomerGroup::where('default', true)->first();
        $taxClass = $this->getOrCreateTaxClass();
        $country = $this->getOrCreateCountry();

        // Step 2: Create attributes and product types
        $this->command->info('🏷️ Creating attributes and product types...');
        $attributeGroup = $this->getOrCreateAttributeGroup();
        $attributes = Attribute::factory()->count(10)->create([
            'attribute_group_id' => $attributeGroup->id,
        ]);
        $productTypes = ProductType::factory()->count(5)->create();

        // Step 3: Create collections
        $this->command->info('📚 Creating collections...');
        $collectionGroup = $this->getOrCreateCollectionGroup();
        $collections = Collection::factory()
            ->count(15)
            ->create([
                'collection_group_id' => $collectionGroup->id,
            ]);

        // Step 4: Create products with variants and prices
        $this->command->info('🛍️ Creating products with variants...');
        $products = Product::factory()
            ->count(50)
            ->published()
            ->withBrand()
            ->create();

        // Attach products to channels and collections
        foreach ($products as $product) {
            $product->channels()->syncWithoutDetaching([$channel->id]);
            
            // Attach to random collections
            $selectedCollections = $collections->random(fake()->numberBetween(1, 4));
            $collectionData = [];
            foreach ($selectedCollections as $position => $collection) {
                $collectionData[$collection->id] = ['position' => $position + 1];
            }
            $product->collections()->sync($collectionData);
        }

        // Create variants and prices for products
        $allVariants = collect();
        foreach ($products as $product) {
            $variants = ProductVariant::factory()
                ->count(fake()->numberBetween(2, 6))
                ->create([
                    'product_id' => $product->id,
                    'tax_class_id' => $taxClass->id,
                ]);

            foreach ($variants as $variant) {
                Price::create([
                    'price' => fake()->randomFloat(2, 10, 2000),
                    'compare_price' => fake()->optional(0.3)->randomFloat(2, 2000, 4000),
                    'currency_id' => $currency->id,
                    'priceable_type' => ProductVariant::class,
                    'priceable_id' => $variant->id,
                ]);
                $allVariants->push($variant);
            }
        }

        // Step 5: Create customers with addresses
        $this->command->info('👥 Creating customers with addresses...');
        $customers = Customer::factory()->count(25)->create();
        
        foreach ($customers as $customer) {
            // Ensure every customer belongs to at least the default group (needed for customer-group pricing/discounts).
            if ($customerGroup) {
                $extraGroupHandles = collect(array_keys($customerGroups))
                    ->reject(fn ($h) => $h === CustomerGroupSeeder::DEFAULT_HANDLE)
                    ->values();

                $attachIds = [$customerGroup->id];

                // Give some customers an extra group so customer-group pricing is easy to test.
                if ($extraGroupHandles->isNotEmpty() && fake()->boolean(40)) {
                    $extra = $customerGroups[$extraGroupHandles->random()] ?? null;
                    if ($extra) {
                        $attachIds[] = $extra->id;
                    }
                }

                $customer->customerGroups()->syncWithoutDetaching($attachIds);
            }

            // Create 1-3 addresses per customer
            $addressCount = fake()->numberBetween(1, 3);
            $addresses = Address::factory()
                ->count($addressCount)
                ->create([
                    'customer_id' => $customer->id,
                    'country_id' => $country->id,
                ]);

            // Set first address as default shipping, second as default billing
            if ($addresses->count() > 0) {
                $addresses->first()->update(['shipping_default' => true]);
            }
            if ($addresses->count() > 1) {
                $addresses->skip(1)->first()->update(['billing_default' => true]);
            }
        }

        // Step 6: Create users and associate with customers
        $this->command->info('👤 Creating users...');
        $users = User::factory()->count(20)->create();
        
        foreach ($users as $index => $user) {
            if ($customers->has($index)) {
                $customers[$index]->users()->attach($user->id);
            }
        }

        // Step 7: Create carts with cart lines
        $this->command->info('🛒 Creating carts with items...');
        // Ensure currency exists before creating carts to avoid race conditions
        $currency->refresh();
        $carts = Cart::factory()
            ->count(30)
            ->create([
                'currency_id' => $currency->id,
            ]);

        foreach ($carts as $cart) {
            // Add 1-5 items to each cart
            $lineCount = fake()->numberBetween(1, 5);
            $selectedVariants = $allVariants->random($lineCount);
            
            foreach ($selectedVariants as $variant) {
                CartLine::factory()->create([
                    'cart_id' => $cart->id,
                    'purchasable_type' => ProductVariant::class,
                    'purchasable_id' => $variant->id,
                    'quantity' => fake()->numberBetween(1, 5),
                ]);
            }

            // Associate some carts with users/customers
            if (fake()->boolean(60)) {
                $user = $users->random();
                $cart->update(['user_id' => $user->id]);
                
                if ($user->customers()->exists()) {
                    $cart->update(['customer_id' => $user->customers()->first()->id]);
                }
            }
        }

        // Step 8: Create URLs for products
        $this->command->info('🔗 Creating product URLs...');
        foreach ($products as $product) {
            UrlFactory::new()
                ->forElement($product)
                ->default()
                ->create([
                    'slug' => str($product->translateAttribute('name'))->slug(),
                ]);
        }

        // Step 9: Create tags and attach to products
        $this->command->info('🏷️ Creating tags...');
        $tags = TagFactory::new()->count(15)->create();
        foreach ($products->random(30) as $product) {
            $product->tags()->attach($tags->random(fake()->numberBetween(1, 3))->pluck('id')->toArray());
        }

        // Step 10: Create discounts
        $this->command->info('💰 Creating discounts...');
        $discounts = DiscountFactory::new()
            ->count(10)
            ->active()
            ->create();
        
        // Mix of percentage and fixed discounts
        DiscountFactory::new()
            ->count(5)
            ->percentage(fake()->numberBetween(10, 50))
            ->withCoupon()
            ->active()
            ->create();
        
        DiscountFactory::new()
            ->count(5)
            ->fixed(fake()->numberBetween(1000, 10000))
            ->withCoupon()
            ->active()
            ->create();

        // Step 11: Create orders with order lines
        $this->command->info('📦 Creating orders...');
        $orders = Order::factory()
            ->count(40)
            ->create();

        foreach ($orders as $order) {
            // Add 1-6 items to each order
            $lineCount = fake()->numberBetween(1, 6);
            $selectedVariants = $allVariants->random($lineCount);
            
            $orderSubTotal = 0;
            foreach ($selectedVariants as $variant) {
                $quantity = fake()->numberBetween(1, 3);
                $unitPrice = (int) ($variant->prices()->first()?->price ?? 1000);
                $lineSubTotal = $unitPrice * $quantity;
                $orderSubTotal += $lineSubTotal;

                OrderLine::factory()->create([
                    'order_id' => $order->id,
                    'purchasable_type' => ProductVariant::class,
                    'purchasable_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'sub_total' => $lineSubTotal,
                    'total' => $lineSubTotal,
                ]);
            }

            // Update order totals
            $discountTotal = (int) ($orderSubTotal * 0.1); // 10% discount
            $shippingTotal = fake()->numberBetween(500, 2000);
            $taxTotal = (int) (($orderSubTotal - $discountTotal) * 0.2);
            $total = $orderSubTotal - $discountTotal + $shippingTotal + $taxTotal;

            $order->update([
                'sub_total' => $orderSubTotal,
                'discount_total' => $discountTotal,
                'shipping_total' => $shippingTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            // Associate some orders with users/customers
            if (fake()->boolean(70)) {
                $user = $users->random();
                $order->update(['user_id' => $user->id]);
                
                if ($user->customers()->exists()) {
                    $order->update(['customer_id' => $user->customers()->first()->id]);
                }
            }
        }

        // Step 12: Create transactions for orders
        $this->command->info('💳 Creating transactions...');
        foreach ($orders->random(30) as $order) {
            TransactionFactory::new()
                ->successful()
                ->create([
                    'order_id' => $order->id,
                    'amount' => $order->total,
                ]);
        }

        // Step 13: Create categories
        $this->command->info('📂 Creating categories...');
        $rootCategories = collect();
        for ($i = 0; $i < 5; $i++) {
            $categoryData = Category::factory()->make()->toArray();
            $category = Category::create($categoryData);
            // Ensure it's a root node
            if ($category->parent_id === null && (!$category->getLft() || !$category->getRgt())) {
                $category->makeRoot()->save();
            }
            $category->refresh();
            // Verify it has lft/rgt values before proceeding
            if (!$category->getLft() || !$category->getRgt()) {
                // Rebuild tree if needed
                Category::fixTree();
                $category->refresh();
            }
            $rootCategories->push($category);
        }
        foreach ($rootCategories as $rootCategory) {
            $rootCategory->refresh(); // Ensure lft/rgt values are loaded
            if ($rootCategory->getLft() && $rootCategory->getRgt()) {
                $childCount = fake()->numberBetween(2, 4);
                for ($j = 0; $j < $childCount; $j++) {
                    $childData = Category::factory()->make()->toArray();
                    unset($childData['parent_id']); // Ensure no parent_id
                    $child = Category::create($childData);
                    $rootCategory->appendNode($child);
                }
            }
        }

        // Step 14: Create reviews with media and helpful votes
        $this->command->info('⭐ Creating reviews...');
        $customers = Customer::all();
        $reviews = collect();
        foreach ($products->random(30) as $product) {
            $productReviews = Review::factory()
                ->count(fake()->numberBetween(1, 5))
                ->create([
                    'product_id' => $product->id,
                ]);
            $reviews = $reviews->merge($productReviews);
        }

        // Add media to some reviews
        foreach ($reviews->random(fake()->numberBetween(10, 20)) as $review) {
            \App\Models\ReviewMedia::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create([
                    'review_id' => $review->id,
                ]);
        }

        // Add helpful votes to some reviews
        foreach ($reviews->random(fake()->numberBetween(15, 30)) as $review) {
            \App\Models\ReviewHelpfulVote::factory()
                ->count(fake()->numberBetween(1, 5))
                ->create([
                    'review_id' => $review->id,
                ]);
        }

        // Step 15: Create search analytics and synonyms
        $this->command->info('🔍 Creating search analytics and synonyms...');
        \App\Models\SearchAnalytic::factory()->count(50)->create();
        \App\Models\SearchSynonym::factory()->count(10)->create();

        // Step 16: Create product views
        $this->command->info('👁️ Creating product views...');
        \App\Models\ProductView::factory()->count(200)->create();
        \App\Models\ProductView::factory()->count(50)->recent()->create();

        // Step 17: Create product purchase associations
        $this->command->info('🛒 Creating product purchase associations...');
        foreach ($products->random(20) as $product) {
            $associatedProducts = $products->where('id', '!=', $product->id)->random(fake()->numberBetween(3, 8));
            foreach ($associatedProducts as $associatedProduct) {
                \App\Models\ProductPurchaseAssociation::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'associated_product_id' => $associatedProduct->id,
                    ],
                    \App\Models\ProductPurchaseAssociation::factory()->make([
                        'product_id' => $product->id,
                        'associated_product_id' => $associatedProduct->id,
                    ])->toArray()
                );
            }
        }

        // Step 18: Create recommendation rules and clicks
        $this->command->info('💡 Creating recommendation rules...');
        foreach ($products->random(30) as $product) {
            \App\Models\RecommendationRule::factory()
                ->count(fake()->numberBetween(2, 5))
                ->withProducts($product, $products->random())
                ->create();
        }

        $this->command->info('🖱️ Creating recommendation clicks...');
        \App\Models\RecommendationClick::factory()->count(150)->create();
        \App\Models\RecommendationClick::factory()->count(30)->converted()->create();

        // Step 19: Create order status history
        $this->command->info('📋 Creating order status history...');
        foreach ($orders->random(20) as $order) {
            \App\Models\OrderStatusHistory::factory()
                ->count(fake()->numberBetween(2, 5))
                ->forOrder($order)
                ->create();
        }

        $this->command->info('✅ Complete seeding finished!');
        $this->command->info("📊 Created:");
        $this->command->info("   - {$products->count()} products");
        $this->command->info("   - {$allVariants->count()} variants");
        $this->command->info("   - {$collections->count()} collections");
        $this->command->info("   - {$customers->count()} customers");
        $this->command->info("   - {$users->count()} users");
        $this->command->info("   - {$carts->count()} carts");
        $this->command->info("   - {$orders->count()} orders");
    }

    protected function getOrCreateChannel(): Channel
    {
        return Channel::firstOrCreate(
            ['handle' => 'webstore'],
            [
                'name' => 'Web Store',
                'url' => 'http://localhost',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateCurrency(): Currency
    {
        return Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1.00,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]
        );
    }

    protected function getOrCreateLanguage(): Language
    {
        return Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateTaxClass(): TaxClass
    {
        return TaxClass::firstOrCreate(
            ['name' => 'Standard Tax'],
            [
                'name' => 'Standard Tax',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateCountry(): Country
    {
        return Country::firstOrCreate(
            ['iso2' => 'US'],
            [
                'name' => 'United States',
                'iso3' => 'USA',
                'iso2' => 'US',
                'phonecode' => '1',
                'capital' => 'Washington',
                'currency' => 'USD',
                'native' => 'United States',
                'emoji' => '🇺🇸',
                'emoji_u' => 'U+1F1FA U+1F1F8',
            ]
        );
    }

    protected function getOrCreateAttributeGroup(): \Lunar\Models\AttributeGroup
    {
        return \Lunar\Models\AttributeGroup::firstOrCreate(
            ['handle' => 'product'],
            [
                'name' => [
                    'en' => 'Product',
                ],
                'attributable_type' => \App\Models\Product::class,
                'position' => 0,
            ]
        );
    }

    protected function getOrCreateCollectionGroup(): CollectionGroup
    {
        return CollectionGroup::firstOrCreate(
            ['handle' => 'default'],
            [
                'name' => 'Default',
            ]
        );
    }
}
