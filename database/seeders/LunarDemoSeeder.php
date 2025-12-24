<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Lunar\Associations\AssociationManager;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductAssociation;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\Tag;
use Lunar\Models\Url;

class LunarDemoSeeder extends Seeder
{
    /**
     * Seed the application's database with demo Lunar data.
     */
    public function run(): void
    {
        $this->command->info('Seeding Lunar demo data...');

        // Step 1: Create Channels, Currencies, and Languages
        $this->command->info('Creating channels, currencies, and languages...');
        $channel = $this->createChannel();
        $currency = $this->createCurrency();
        $language = $this->createLanguage();

        // Step 2: Create Attribute Groups and Attributes
        $this->command->info('Creating attributes...');
        $attributes = $this->createAttributes();

        // Step 3: Create Product Types
        $this->command->info('Creating product types...');
        $productType = $this->createProductType($attributes);

        // Step 4: Create Collection Groups and Collections
        $this->command->info('Creating collections...');
        $collections = $this->createCollections();

        // Step 5: Create Products with Variants and Prices
        $this->command->info('Creating products...');
        $products = $this->createProducts($productType, $channel, $currency, $collections);

        // Step 6: Create Tags and attach to products
        $this->command->info('Creating tags...');
        $this->createTags($products);

        // Step 7: Create Product Associations (cross-sell, up-sell, alternate)
        $this->command->info('Creating product associations...');
        $this->createAssociations($products);

        $this->command->info('Lunar demo data seeded successfully!');
    }

    protected function createChannel(): Channel
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

    protected function createCurrency(): Currency
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

    protected function createLanguage(): Language
    {
        return Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'default' => true,
            ]
        );
    }

    protected function createAttributes(): array
    {
        // Create attribute groups
        $productGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'product'],
            ['name' => 'Product']
        );

        $shippingGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'shipping'],
            ['name' => 'Shipping']
        );

        // Create attributes following Lunar Attributes documentation
        // See: https://docs.lunarphp.com/1.x/reference/attributes
        $attributes = [];

        // Name attribute (required, system attribute)
        // Uses TranslatedText for multi-language support
        $attributes['name'] = Attribute::firstOrCreate(
            ['handle' => 'name'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $productGroup->id,
                'position' => 1,
                'name' => [
                    'en' => 'Name',
                ],
                'type' => \Lunar\FieldTypes\TranslatedText::class,
                'required' => true,
                'searchable' => true,
                'filterable' => false,
                'system' => true,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [
                    'richtext' => false,
                ],
            ]
        );

        // Description attribute
        // Uses Text field type (supports single-line, multi-line, rich text)
        $attributes['description'] = Attribute::firstOrCreate(
            ['handle' => 'description'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $productGroup->id,
                'position' => 2,
                'name' => [
                    'en' => 'Description',
                ],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => false,
                'system' => false,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        // Color attribute (filterable for faceted search)
        // Product variant attributes use 'product_variant' as attribute_type
        $attributes['color'] = Attribute::firstOrCreate(
            ['handle' => 'color'],
            [
                'attribute_type' => 'product_variant',
                'attribute_group_id' => $productGroup->id,
                'position' => 3,
                'name' => [
                    'en' => 'Color',
                ],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        // Size attribute (filterable for faceted search)
        $attributes['size'] = Attribute::firstOrCreate(
            ['handle' => 'size'],
            [
                'attribute_type' => 'product_variant',
                'attribute_group_id' => $productGroup->id,
                'position' => 4,
                'name' => [
                    'en' => 'Size',
                ],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        // Material attribute
        $attributes['material'] = Attribute::firstOrCreate(
            ['handle' => 'material'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $productGroup->id,
                'position' => 5,
                'name' => [
                    'en' => 'Material',
                ],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        // Weight attribute (Number field type)
        $attributes['weight'] = Attribute::firstOrCreate(
            ['handle' => 'weight'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $productGroup->id,
                'position' => 6,
                'name' => [
                    'en' => 'Weight (kg)',
                ],
                'type' => \Lunar\FieldTypes\Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        // Create SEO attribute group
        $seoGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'seo'],
            ['name' => 'SEO']
        );

        // Meta Title attribute (SEO group)
        $attributes['meta_title'] = Attribute::firstOrCreate(
            ['handle' => 'meta_title'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $seoGroup->id,
                'position' => 1,
                'name' => [
                    'en' => 'Meta Title',
                ],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'section' => 'seo',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        // Meta Description attribute (SEO group)
        $attributes['meta_description'] = Attribute::firstOrCreate(
            ['handle' => 'meta_description'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $seoGroup->id,
                'position' => 2,
                'name' => [
                    'en' => 'Meta Description',
                ],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'section' => 'seo',
                'default_value' => null,
                'configuration' => [],
            ]
        );

        return $attributes;
    }

    protected function createProductType(array $attributes): ProductType
    {
        $productType = ProductType::firstOrCreate(
            ['handle' => 'physical'],
            ['name' => 'Physical Product']
        );

        // Attach attributes to product type if not already attached
        $attributeIds = collect($attributes)->pluck('id')->toArray();
        $existingIds = $productType->mappedAttributes()->pluck('lunar_attributes.id')->toArray();
        $newIds = array_diff($attributeIds, $existingIds);
        
        if (!empty($newIds)) {
            $productType->mappedAttributes()->attach($newIds);
        }

        return $productType;
    }

    protected function createCollections(): array
    {
        // Create collection group following Lunar Collections documentation
        // See: https://docs.lunarphp.com/1.x/reference/collections
        $group = CollectionGroup::firstOrCreate(
            ['handle' => 'main-catalogue'],
            ['name' => 'Main Catalogue']
        );

        $collections = [];

        // Create collections using attribute_data with FieldType objects
        // Collections follow the same pattern as products for attributes
        $collections['electronics'] = Collection::firstOrCreate(
            ['handle' => 'electronics'],
            [
                'collection_group_id' => $group->id,
                'type' => 'static',
                'sort' => 'min_price:asc', // Sort by minimum price ascending
                'attribute_data' => [
                    'name' => new \Lunar\FieldTypes\TranslatedText(collect([
                        'en' => new \Lunar\FieldTypes\Text('Electronics'),
                    ])),
                ],
            ]
        );

        $collections['clothing'] = Collection::firstOrCreate(
            ['handle' => 'clothing'],
            [
                'collection_group_id' => $group->id,
                'type' => 'static',
                'sort' => 'custom', // Custom sorting (manual positions)
                'attribute_data' => [
                    'name' => new \Lunar\FieldTypes\TranslatedText(collect([
                        'en' => new \Lunar\FieldTypes\Text('Clothing'),
                    ])),
                ],
            ]
        );

        $collections['home'] = Collection::firstOrCreate(
            ['handle' => 'home'],
            [
                'collection_group_id' => $group->id,
                'type' => 'static',
                'sort' => 'sku:asc', // Sort by SKU ascending
                'attribute_data' => [
                    'name' => new \Lunar\FieldTypes\TranslatedText(collect([
                        'en' => new \Lunar\FieldTypes\Text('Home & Garden'),
                    ])),
                ],
            ]
        );

        // Create a child collection to demonstrate nested collections
        // Using appendNode from Laravel Nested Set package
        $collections['home-electronics'] = Collection::firstOrCreate(
            ['handle' => 'home-electronics'],
            [
                'collection_group_id' => $group->id,
                'type' => 'static',
                'sort' => 'min_price:desc', // Sort by minimum price descending
                'attribute_data' => [
                    'name' => new \Lunar\FieldTypes\TranslatedText(collect([
                        'en' => new \Lunar\FieldTypes\Text('Home Electronics'),
                    ])),
                ],
            ]
        );

        // Make home-electronics a child of home collection
        // This demonstrates the nested set hierarchy feature using appendNode
        // The parent collection calls appendNode with the child collection
        try {
            if (!$collections['home-electronics']->isDescendantOf($collections['home'])) {
                $collections['home']->appendNode($collections['home-electronics']);
            }
        } catch (\Exception $e) {
            // Collection might already be attached, ignore
        }

        return $collections;
    }

    protected function createProducts(ProductType $productType, Channel $channel, Currency $currency, array $collections): array
    {
        $products = [];

        // Product 1: Wireless Headphones
        $products['headphones'] = $this->createProduct([
            'name' => 'Premium Wireless Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation and premium sound quality.',
            'material' => 'Plastic, Metal',
            'weight' => 0.25, // 250g
            'meta_title' => 'Premium Wireless Headphones | Noise Cancelling Audio',
            'meta_description' => 'Discover premium wireless headphones with active noise cancellation and superior sound quality.',
        ], $productType, $channel, $currency, [$collections['electronics']], [
            ['sku' => 'HEAD-001', 'price' => 19999, 'stock' => 50], // $199.99
        ]);

        // Product 2: T-Shirt
        $products['tshirt'] = $this->createProduct([
            'name' => 'Classic Cotton T-Shirt',
            'description' => 'Comfortable 100% cotton t-shirt available in multiple sizes and colors.',
            'material' => 'Cotton',
            'weight' => 0.15, // 150g
            'meta_title' => 'Classic Cotton T-Shirt | Comfortable & Versatile',
            'meta_description' => 'Shop our classic cotton t-shirts in various sizes and colors. 100% cotton comfort.',
        ], $productType, $channel, $currency, [$collections['clothing']], [
            ['sku' => 'TSH-S-BL', 'price' => 2499, 'stock' => 100, 'attributes' => ['size' => 'Small', 'color' => 'Blue']], // $24.99
            ['sku' => 'TSH-M-BL', 'price' => 2499, 'stock' => 100, 'attributes' => ['size' => 'Medium', 'color' => 'Blue']],
            ['sku' => 'TSH-L-BL', 'price' => 2499, 'stock' => 100, 'attributes' => ['size' => 'Large', 'color' => 'Blue']],
            ['sku' => 'TSH-M-RD', 'price' => 2499, 'stock' => 100, 'attributes' => ['size' => 'Medium', 'color' => 'Red']],
        ]);

        // Product 3: Coffee Maker
        $products['coffeemaker'] = $this->createProduct([
            'name' => 'Automatic Coffee Maker',
            'description' => 'Programmable coffee maker with thermal carafe and auto shut-off.',
            'material' => 'Plastic, Glass',
            'weight' => 2.5, // 2.5kg
            'meta_title' => 'Automatic Coffee Maker | Programmable with Thermal Carafe',
            'meta_description' => 'Wake up to perfect coffee with our programmable coffee maker featuring thermal carafe technology.',
        ], $productType, $channel, $currency, [$collections['home']], [
            ['sku' => 'COF-001', 'price' => 8999, 'stock' => 30], // $89.99
        ]);

        // Product 4: Smartphone
        $products['smartphone'] = $this->createProduct([
            'name' => 'Smartphone Pro Max',
            'description' => 'Latest generation smartphone with advanced camera and processing power.',
            'material' => 'Glass, Aluminum',
            'weight' => 0.22, // 220g
            'meta_title' => 'Smartphone Pro Max | Advanced Camera & Processing',
            'meta_description' => 'Experience the latest technology with our Pro Max smartphone featuring advanced camera systems and powerful processing.',
        ], $productType, $channel, $currency, [$collections['electronics']], [
            ['sku' => 'PHN-128-BL', 'price' => 99900, 'stock' => 25, 'attributes' => ['color' => 'Black']], // $999.00
            ['sku' => 'PHN-256-BL', 'price' => 114900, 'stock' => 15, 'attributes' => ['color' => 'Black']], // $1149.00
        ]);

        // Product 5: Garden Tool Set
        $products['gardentools'] = $this->createProduct([
            'name' => 'Professional Garden Tool Set',
            'description' => 'Complete set of professional-grade garden tools for all your gardening needs.',
            'material' => 'Steel, Wood',
            'weight' => 3.2, // 3.2kg
            'meta_title' => 'Professional Garden Tool Set | Complete Gardening Kit',
            'meta_description' => 'Professional-grade garden tool set with everything you need for your gardening projects.',
        ], $productType, $channel, $currency, [$collections['home']], [
            ['sku' => 'GDT-001', 'price' => 4999, 'stock' => 40], // $49.99
        ]);

        return $products;
    }

    protected function createProduct(
        array $attributeData,
        ProductType $productType,
        Channel $channel,
        Currency $currency,
        array $collections,
        array $variants
    ): Product {
        // Build attribute data using proper FieldType objects
        // See: https://docs.lunarphp.com/1.x/reference/attributes
        $data = collect();
        
        // Handle name as TranslatedText (multi-language support)
        if (isset($attributeData['name'])) {
            $data['name'] = new \Lunar\FieldTypes\TranslatedText(collect([
                'en' => new \Lunar\FieldTypes\Text($attributeData['name']),
            ]));
        }
        
        // Handle description as Text
        if (isset($attributeData['description'])) {
            $data['description'] = new \Lunar\FieldTypes\Text($attributeData['description']);
        }
        
        // Handle material as Text
        if (isset($attributeData['material'])) {
            $data['material'] = new \Lunar\FieldTypes\Text($attributeData['material']);
        }
        
        // Handle color as Text (if provided for product, though typically it's a variant attribute)
        if (isset($attributeData['color'])) {
            $data['color'] = new \Lunar\FieldTypes\Text($attributeData['color']);
        }
        
        // Handle weight as Number (if provided)
        if (isset($attributeData['weight'])) {
            $data['weight'] = new \Lunar\FieldTypes\Number($attributeData['weight']);
        }
        
        // Add SEO attributes if provided
        if (isset($attributeData['meta_title'])) {
            $data['meta_title'] = new \Lunar\FieldTypes\Text($attributeData['meta_title']);
        }
        
        if (isset($attributeData['meta_description'])) {
            $data['meta_description'] = new \Lunar\FieldTypes\Text($attributeData['meta_description']);
        }

        $product = Product::create([
            'product_type_id' => $productType->id,
            'status' => 'published',
            'attribute_data' => $data,
        ]);

        // Attach to channel
        $product->channels()->attach($channel->id);

        // Attach to collections with positions using sync()
        // Following Lunar Collections documentation: https://docs.lunarphp.com/1.x/reference/collections
        // The key in the array is the product id, value contains position
        foreach ($collections as $index => $collection) {
            // Use syncWithoutDetaching to add products with positions
            // Format: [product_id => ['position' => int]]
            $collection->products()->syncWithoutDetaching([
                $product->id => ['position' => $index + 1],
            ]);
        }

        // Create variants
        foreach ($variants as $index => $variantData) {
            // Handle variant attributes using proper FieldType objects
            // Variant attributes typically include size, color, etc.
            $variantAttributes = $variantData['attributes'] ?? [];
            $variantAttributeData = collect();
            
            foreach ($variantAttributes as $attrKey => $attrValue) {
                // Use Text field type for variant attributes like size, color
                $variantAttributeData[$attrKey] = new \Lunar\FieldTypes\Text($attrValue);
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'tax_class_id' => null, // Can be set if tax classes exist
                'attribute_data' => $variantAttributeData->count() > 0 ? $variantAttributeData : null,
                'stock' => $variantData['stock'] ?? 0,
                'backorder' => 0,
                'purchasable' => 'always',
                'shippable' => true,
                'unit_quantity' => 1,
            ]);

            // Create price for variant
            Price::create([
                'priceable_type' => ProductVariant::class,
                'priceable_id' => $variant->id,
                'price' => $variantData['price'], // Price in smallest currency unit (cents for USD)
                'currency_id' => $currency->id,
                'compare_price' => $variantData['compare_price'] ?? null,
                'tier' => 1,
            ]);
        }

        // Create URL
        $slug = Str::slug($attributeData['name']);
        Url::create([
            'language_id' => Language::where('code', 'en')->first()->id,
            'element_type' => Product::class,
            'element_id' => $product->id,
            'slug' => $slug,
            'default' => true,
        ]);

        return $product;
    }

    protected function createTags(array $products): void
    {
        $tags = [
            'new' => Tag::firstOrCreate(['value' => 'new']),
            'bestseller' => Tag::firstOrCreate(['value' => 'bestseller']),
            'sale' => Tag::firstOrCreate(['value' => 'sale']),
            'premium' => Tag::firstOrCreate(['value' => 'premium']),
        ];

        // Attach tags to products (using direct relationship)
        if (isset($products['headphones'])) {
            $products['headphones']->tags()->attach($tags['premium']->id);
            $products['headphones']->tags()->attach($tags['bestseller']->id);
        }

        if (isset($products['smartphone'])) {
            $products['smartphone']->tags()->attach($tags['new']->id);
            $products['smartphone']->tags()->attach($tags['premium']->id);
        }

        if (isset($products['tshirt'])) {
            $products['tshirt']->tags()->attach($tags['sale']->id);
        }
    }

    protected function createAssociations(array $products): void
    {
        if (!isset($products['headphones']) || !isset($products['smartphone'])) {
            return;
        }

        // Use AssociationManager for synchronous association creation in seeders
        // The Product::associate() method dispatches a job, which we avoid in seeders
        // See: https://docs.lunarphp.com/1.x/reference/associations
        $manager = new AssociationManager();
        
        // Cross-sell: Headphones with Smartphone (complementary products)
        // When viewing a smartphone, suggest headphones as an add-on
        $manager->associate(
            $products['smartphone'],
            $products['headphones'],
            ProductAssociationEnum::CROSS_SELL
        );

        // Up-sell: Smartphone storage upgrade (higher value product)
        // When viewing smartphone, suggest a higher storage variant (if available)
        if (isset($products['coffeemaker'])) {
            // Example: up-sell premium coffee maker when viewing basic one
            $manager->associate(
                $products['coffeemaker'],
                $products['smartphone'],
                ProductAssociationEnum::UP_SELL
            );
        }

        // Alternate: T-Shirt alternatives (alternative products)
        // When viewing t-shirt, show alternative clothing options
        if (isset($products['tshirt']) && isset($products['gardentools'])) {
            $manager->associate(
                $products['tshirt'],
                $products['gardentools'],
                ProductAssociationEnum::ALTERNATE
            );
        }

        // Cross-sell: Garden tools with home items (complementary products)
        // When viewing coffee maker, suggest garden tools for home improvement
        if (isset($products['coffeemaker']) && isset($products['gardentools'])) {
            $manager->associate(
                $products['coffeemaker'],
                $products['gardentools'],
                ProductAssociationEnum::CROSS_SELL
            );
        }

        // Multiple products association example
        // Associate multiple products at once (cross-sell multiple accessories)
        // This demonstrates the array syntax from the docs
        if (isset($products['headphones']) && isset($products['coffeemaker'])) {
            $manager->associate(
                $products['smartphone'],
                [$products['headphones'], $products['coffeemaker']],
                ProductAssociationEnum::CROSS_SELL
            );
        }
    }
}

