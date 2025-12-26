<?php

namespace Database\Seeders;

use App\Lunar\Attributes\AttributeHelper;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\TranslationService;
use Database\Factories\CollectionFactory;
use Database\Factories\CollectionGroupFactory;
use Database\Factories\PriceFactory;
use Database\Factories\ProductFactory;
use Database\Factories\ProductTypeFactory;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Seeder;
use Lunar\Models\CollectionGroup;

/**
 * Example seeder demonstrating how to create multilingual content
 * with Lunar's translation system.
 *
 * This seeder shows:
 * - Creating products with translations
 * - Creating collections with translations
 * - Using the TranslationService for fallback support
 * - Best practices for multilingual content
 */
class MultilingualContentExampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating multilingual content examples...');

        // Get or create required models
        $productType = ProductType::query()->first()
            ?? ProductTypeFactory::new()->simple()->create();

        $collectionGroup = CollectionGroup::query()->first()
            ?? CollectionGroupFactory::new()
                ->state(['name' => 'Default', 'handle' => 'default'])
                ->create();

        // Example 1: Product with full translations
        $this->createMultilingualProduct($productType, $collectionGroup);

        // Example 2: Product with partial translations (demonstrates fallback)
        $this->createProductWithPartialTranslations($productType, $collectionGroup);

        // Example 3: Collection with translations
        $this->createMultilingualCollection($collectionGroup);

        $this->command->info('Multilingual content examples created.');
    }

    /**
     * Create a product with translations in all languages.
     */
    protected function createMultilingualProduct(ProductType $productType, CollectionGroup $collectionGroup): void
    {
        $this->command->info('  Creating product with full translations...');

        $product = ProductFactory::new()
            ->state([
                'product_type_id' => $productType->id,
                'status' => 'published',
                'attribute_data' => collect([
                    'name' => AttributeHelper::translatedText([
                        'en' => 'Premium Wireless Headphones',
                        'fr' => 'Ecouteurs sans fil premium',
                        'es' => 'Auriculares inalambricos premium',
                        'de' => 'Premium-Kopfhorer',
                        'zh' => 'Premium Wireless Headphones',
                    ]),
                    'description' => AttributeHelper::translatedText([
                        'en' => 'High-quality wireless headphones with active noise cancellation technology. Perfect for music lovers and professionals.',
                        'fr' => 'Ecouteurs sans fil de haute qualite avec annulation active du bruit. Parfait pour les amateurs de musique et les professionnels.',
                        'es' => 'Auriculares inalambricos de alta calidad con cancelacion activa de ruido. Perfecto para amantes de la musica y profesionales.',
                        'de' => 'Hochwertige Funkkopfhorer mit aktiver Gerauschunterdruckung. Perfekt fur Musikliebhaber und Profis.',
                        'zh' => 'High-quality wireless headphones with active noise cancellation technology.',
                    ]),
                ]),
            ])
            ->create();

        // Create variant
        $variant = ProductVariantFactory::new()
            ->withoutPrices()
            ->create([
                'product_id' => $product->id,
                'sku' => 'HEAD-001-PREM',
            ]);

        PriceFactory::new()
            ->forVariant($variant)
            ->create([
                'price' => 19999,
            ]);

        // Associate with collection
        $this->associateWithCollection($product, $collectionGroup, 'Electronics');

        $this->command->info("    Created product: {$product->translateAttribute('name')}");
    }

    /**
     * Create a product with partial translations (demonstrates fallback).
     */
    protected function createProductWithPartialTranslations(ProductType $productType, CollectionGroup $collectionGroup): void
    {
        $this->command->info('  Creating product with partial translations (fallback demo)...');

        $product = ProductFactory::new()
            ->state([
                'product_type_id' => $productType->id,
                'status' => 'published',
                'attribute_data' => collect([
                    'name' => AttributeHelper::translatedText([
                        'en' => 'Leather Boots',
                        'fr' => 'Bottes en cuir',
                        // Missing es, de, zh - will fallback to English.
                    ]),
                    'description' => AttributeHelper::translatedText([
                        'en' => 'Premium leather boots with excellent durability and style.',
                        'fr' => 'Bottes en cuir premium avec une excellente durabilite et un style elegant.',
                        // Missing es, de, zh - will fallback to English.
                    ]),
                ]),
            ])
            ->create();

        // Create variant
        $variant = ProductVariantFactory::new()
            ->withoutPrices()
            ->create([
                'product_id' => $product->id,
                'sku' => 'BOOT-001-LEATH',
            ]);

        PriceFactory::new()
            ->forVariant($variant)
            ->create([
                'price' => 24999,
            ]);

        // Associate with collection
        $this->associateWithCollection($product, $collectionGroup, 'Footwear');

        // Demonstrate fallback
        $this->command->info("    Created product: {$product->translateAttribute('name')}");
        $this->command->info('      English: ' . TranslationService::translate($product, 'name', 'en'));
        $this->command->info('      French: ' . TranslationService::translate($product, 'name', 'fr'));
        $this->command->info('      Spanish (fallback): ' . TranslationService::translate($product, 'name', 'es'));
    }

    /**
     * Create a collection with translations.
     */
    protected function createMultilingualCollection(CollectionGroup $collectionGroup): void
    {
        $this->command->info('  Creating multilingual collection...');

        $collection = CollectionFactory::new()
            ->state([
                'collection_group_id' => $collectionGroup->id,
                'type' => 'static',
                'sort' => 'custom',
                'attribute_data' => collect([
                    'name' => AttributeHelper::translatedText([
                        'en' => 'Electronics',
                        'fr' => 'Electronique',
                        'es' => 'Electronica',
                        'de' => 'Elektronik',
                        'zh' => 'Electronics',
                    ]),
                    'description' => AttributeHelper::translatedText([
                        'en' => 'All electronics and gadgets',
                        'fr' => 'Tous les appareils electroniques et gadgets',
                        'es' => 'Todos los aparatos electronicos y gadgets',
                        'de' => 'Alle Elektronik und Gadgets',
                        'zh' => 'All electronics and gadgets',
                    ]),
                ]),
            ])
            ->create();

        $this->command->info("    Created collection: {$collection->translateAttribute('name')}");
    }

    /**
     * Associate product with collection (create collection if needed).
     */
    protected function associateWithCollection(Product $product, CollectionGroup $collectionGroup, string $collectionName): void
    {
        $collection = Collection::query()
            ->where('collection_group_id', $collectionGroup->id)
            ->where('attribute_data->name->en', $collectionName)
            ->first();

        if (!$collection) {
            $collection = CollectionFactory::new()
                ->state([
                    'collection_group_id' => $collectionGroup->id,
                    'type' => 'static',
                    'sort' => 'custom',
                    'attribute_data' => collect([
                        'name' => AttributeHelper::translatedText([
                            'en' => $collectionName,
                            'fr' => $collectionName,
                            'es' => $collectionName,
                            'de' => $collectionName,
                            'zh' => $collectionName,
                        ]),
                    ]),
                ])
                ->create();
        }

        $product->collections()->syncWithoutDetaching([$collection->id]);
    }
}
