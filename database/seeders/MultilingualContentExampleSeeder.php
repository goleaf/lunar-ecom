<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\ProductVariant;
use App\Lunar\Attributes\AttributeHelper;
use App\Services\TranslationService;

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
        $this->command->info('🌐 Creating multilingual content examples...');

        // Get or create required models
        $productType = ProductType::first();
        if (!$productType) {
            $this->command->error('No product type found. Please create one first.');
            return;
        }

        $collectionGroup = CollectionGroup::first();
        if (!$collectionGroup) {
            $this->command->error('No collection group found. Please create one first.');
            return;
        }

        // Example 1: Product with full translations
        $this->createMultilingualProduct($productType, $collectionGroup);
        
        // Example 2: Product with partial translations (demonstrates fallback)
        $this->createProductWithPartialTranslations($productType, $collectionGroup);
        
        // Example 3: Collection with translations
        $this->createMultilingualCollection($collectionGroup);

        $this->command->info('✅ Multilingual content examples created!');
    }

    /**
     * Create a product with translations in all languages.
     */
    protected function createMultilingualProduct(ProductType $productType, CollectionGroup $collectionGroup): void
    {
        $this->command->info('  Creating product with full translations...');

        $product = Product::create([
            'product_type_id' => $productType->id,
            'status' => 'published',
            'attribute_data' => collect([
                'name' => AttributeHelper::translatedText([
                    'en' => 'Premium Wireless Headphones',
                    'fr' => 'Écouteurs sans fil premium',
                    'es' => 'Auriculares inalámbricos premium',
                    'de' => 'Premium-Kopfhörer',
                    'zh' => '高级无线耳机',
                ]),
                'description' => AttributeHelper::translatedText([
                    'en' => 'High-quality wireless headphones with active noise cancellation technology. Perfect for music lovers and professionals.',
                    'fr' => 'Écouteurs sans fil de haute qualité avec technologie d\'annulation active du bruit. Parfait pour les amateurs de musique et les professionnels.',
                    'es' => 'Auriculares inalámbricos de alta calidad con tecnología de cancelación activa de ruido. Perfecto para amantes de la música y profesionales.',
                    'de' => 'Hochwertige Funkkopfhörer mit aktiver Geräuschunterdrückung. Perfekt für Musikliebhaber und Profis.',
                    'zh' => '高品质无线耳机，具有主动降噪技术。非常适合音乐爱好者和专业人士。',
                ]),
            ]),
        ]);

        // Create variant
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HEAD-001-PREM',
            'price' => 19999, // $199.99 in cents
        ]);

        // Associate with collection
        $this->associateWithCollection($product, $collectionGroup, 'Electronics');

        $this->command->info("    ✓ Created product: {$product->translateAttribute('name')}");
    }

    /**
     * Create a product with partial translations (demonstrates fallback).
     */
    protected function createProductWithPartialTranslations(ProductType $productType, CollectionGroup $collectionGroup): void
    {
        $this->command->info('  Creating product with partial translations (fallback demo)...');

        $product = Product::create([
            'product_type_id' => $productType->id,
            'status' => 'published',
            'attribute_data' => collect([
                'name' => AttributeHelper::translatedText([
                    'en' => 'Leather Boots',
                    'fr' => 'Bottes en cuir',
                    // Note: Missing es, de, zh - will fallback to English
                ]),
                'description' => AttributeHelper::translatedText([
                    'en' => 'Premium leather boots with excellent durability and style.',
                    'fr' => 'Bottes en cuir premium avec une excellente durabilité et un style élégant.',
                    // Note: Missing es, de, zh - will fallback to English
                ]),
            ]),
        ]);

        // Create variant
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BOOT-001-LEATH',
            'price' => 24999, // $249.99 in cents
        ]);

        // Associate with collection
        $this->associateWithCollection($product, $collectionGroup, 'Footwear');

        // Demonstrate fallback
        $this->command->info("    ✓ Created product: {$product->translateAttribute('name')}");
        $this->command->info("      English: " . TranslationService::translate($product, 'name', 'en'));
        $this->command->info("      French: " . TranslationService::translate($product, 'name', 'fr'));
        $this->command->info("      Spanish (fallback): " . TranslationService::translate($product, 'name', 'es'));
    }

    /**
     * Create a collection with translations.
     */
    protected function createMultilingualCollection(CollectionGroup $collectionGroup): void
    {
        $this->command->info('  Creating multilingual collection...');

        $collection = Collection::create([
            'collection_group_id' => $collectionGroup->id,
            'attribute_data' => collect([
                'name' => AttributeHelper::translatedText([
                    'en' => 'Electronics',
                    'fr' => 'Électronique',
                    'es' => 'Electrónica',
                    'de' => 'Elektronik',
                    'zh' => '电子产品',
                ]),
                'description' => AttributeHelper::translatedText([
                    'en' => 'All electronics and gadgets',
                    'fr' => 'Tous les appareils électroniques et gadgets',
                    'es' => 'Todos los aparatos electrónicos y gadgets',
                    'de' => 'Alle Elektronik und Gadgets',
                    'zh' => '所有电子产品和小工具',
                ]),
            ]),
        ]);

        $this->command->info("    ✓ Created collection: {$collection->translateAttribute('name')}");
    }

    /**
     * Associate product with collection (create collection if needed).
     */
    protected function associateWithCollection(Product $product, CollectionGroup $collectionGroup, string $collectionName): void
    {
        // Find or create collection
        $collection = Collection::whereHas('attribute_data', function ($query) use ($collectionName) {
            // This is simplified - in reality you'd need to query JSON
        })->first();

        if (!$collection) {
            $collection = Collection::create([
                'collection_group_id' => $collectionGroup->id,
                'attribute_data' => collect([
                    'name' => AttributeHelper::translatedText([
                        'en' => $collectionName,
                        'fr' => $collectionName, // Could add translations here
                        'es' => $collectionName,
                        'de' => $collectionName,
                        'zh' => $collectionName,
                    ]),
                ]),
            ]);
        }

        $product->collections()->sync([$collection->id]);
    }
}

