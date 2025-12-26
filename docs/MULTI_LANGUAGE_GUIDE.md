# 🌐 Multi-Language Support with Lunar

Lunar's built-in multi-language support makes it easy to create content for global audiences. This guide covers all the features and how to use them.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [Setup](#setup)
4. [Translation System](#translation-system)
5. [Language Switcher](#language-switcher)
6. [Fallback Support](#fallback-support)
7. [Seamless Laravel Integration](#seamless-laravel-integration)
8. [Use Cases](#use-cases)
9. [Examples](#examples)

## Overview

Lunar provides a comprehensive multi-language system that works seamlessly with Laravel's localization features. It supports:

- **Database translations** for products, collections, categories, and other content types
- **UI translations** via Laravel's translation files
- **Automatic fallback** to default language for missing translations
- **Language detection** from URL parameters, browser headers, or session
- **Frontend language switcher** for easy language changes

## Key Features

### ✅ Translation System

Translate content like product descriptions, category names, collection titles, and more using Lunar's `TranslatedText` field type.

### ✅ Language Switcher

Easily switch between languages in your frontend with a beautiful dropdown selector.

### ✅ Fallback Support

Define fallback languages for missing translations. The system automatically falls back to the default language.

### ✅ Seamless Integration

Works perfectly with Laravel's localization features (`App::setLocale()`, `__()` helper, etc.).

## Setup

### 1. Seed Languages

Run the language seeder to create all available languages:

```bash
php artisan languages:seed
```

Or use the seeder directly:

```bash
php artisan db:seed --class=LanguageSeeder
```

### 2. Configured Languages

The following languages are configured by default:

| Code | Name | Default |
|------|------|---------|
| `en` | English | ✅ Yes |
| `es` | Spanish | No |
| `fr` | French | No |
| `de` | German | No |
| `zh` | Chinese | No |

### 3. Language Detection Middleware

The `LanguageDetectionMiddleware` automatically detects the language from:

1. **URL Parameter** (`?lang=fr`) - Highest priority
2. **Browser Header** (`Accept-Language`) - Detects from browser preferences
3. **Session** - Previously selected language
4. **Default Language** - Falls back to English

The middleware is already registered in `bootstrap/app.php`.

## Translation System

### Database Translations

Lunar uses the `attribute_data` field with `TranslatedText` field types to store multilingual content.

#### Creating Products with Translations

```php
use Lunar\Models\Product;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use App\Lunar\Attributes\AttributeHelper;

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
            'en' => 'High-quality wireless headphones with active noise cancellation.',
            'fr' => 'Écouteurs sans fil de haute qualité avec annulation active du bruit.',
            'es' => 'Auriculares inalámbricos de alta calidad con cancelación activa de ruido.',
            'de' => 'Hochwertige Funkkopfhörer mit aktiver Geräuschunterdrückung.',
            'zh' => '高品质无线耳机，具有主动降噪功能。',
        ]),
    ]),
]);
```

#### Creating Collections with Translations

```php
use Lunar\Models\Collection;
use App\Lunar\Attributes\AttributeHelper;

$collection = Collection::create([
    'collection_group_id' => $group->id,
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
            // ... other languages
        ]),
    ]),
]);
```

#### Displaying Translated Content

In Blade templates, use `translateAttribute()` which automatically uses the current locale:

```blade
{{-- Product name (automatically translated) --}}
<h1>{{ $product->translateAttribute('name') }}</h1>

{{-- Product description (automatically translated) --}}
<p>{{ $product->translateAttribute('description') }}</p>

{{-- Collection name (automatically translated) --}}
<h2>{{ $collection->translateAttribute('name') }}</h2>

{{-- Specify a specific language --}}
<h1>{{ $product->translateAttribute('name', 'fr') }}</h1>
```

#### Using the AttributeHelper

```php
use App\Lunar\Attributes\AttributeHelper;

// Get translated attribute (uses current locale)
$name = AttributeHelper::get($product, 'name');

// Get attribute in specific language
$nameFr = AttributeHelper::get($product, 'name', 'fr');

// Check if product has attribute
if (AttributeHelper::has($product, 'description')) {
    // ...
}

// Get all attributes as array
$allAttributes = AttributeHelper::all($product);
$allAttributesFr = AttributeHelper::all($product, 'fr');
```

### UI Translations

For UI strings (buttons, labels, messages), use Laravel's translation system with `__()` helper:

#### Translation Files

Translation files are located in `resources/lang/{locale}/frontend.php`:

```php
// resources/lang/en/frontend.php
return [
    'nav' => [
        'products' => 'Products',
        'collections' => 'Collections',
    ],
    'product' => [
        'add_to_cart' => 'Add to Cart',
        'view_details' => 'View Details',
    ],
];
```

```php
// resources/lang/fr/frontend.php
return [
    'nav' => [
        'products' => 'Produits',
        'collections' => 'Collections',
    ],
    'product' => [
        'add_to_cart' => 'Ajouter au panier',
        'view_details' => 'Voir les détails',
    ],
];
```

#### Using in Views

```blade
{{-- Navigation --}}
<a href="{{ route('frontend.products.index') }}">
    {{ __('frontend.nav.products') }}
</a>

{{-- Buttons --}}
<button>{{ __('frontend.product.add_to_cart') }}</button>
```

## Language Switcher

### Frontend Component

The language switcher is automatically included in the navigation bar (`resources/views/frontend/components/language-selector.blade.php`).

Users can:
- View all available languages
- See the current language (highlighted)
- Switch languages with a single click
- See the page automatically reload with new translations

### API Endpoints

#### Get All Languages

```http
GET /language
```

Response:
```json
{
    "languages": [
        {
            "id": 1,
            "code": "en",
            "name": "English",
            "is_default": true
        },
        {
            "id": 2,
            "code": "fr",
            "name": "French",
            "is_default": false
        }
    ],
    "current": {
        "id": 1,
        "code": "en",
        "name": "English",
        "is_default": true
    }
}
```

#### Get Current Language

```http
GET /language/current
```

Response:
```json
{
    "language": {
        "id": 1,
        "code": "en",
        "name": "English",
        "is_default": true
    }
}
```

#### Switch Language

```http
POST /language/switch
Content-Type: application/json

{
    "language": "fr"
}
```

Response:
```json
{
    "success": true,
    "message": "Language switched successfully",
    "language": {
        "id": 2,
        "code": "fr",
        "name": "French",
        "is_default": false
    }
}
```

### Programmatic Language Switching

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use App\Lunar\Languages\LanguageHelper;

// Switch by code
StorefrontSessionHelper::setLanguage('fr');

// Switch by language instance
$language = LanguageHelper::findByCode('de');
StorefrontSessionHelper::setLanguage($language);

// Get current language
$currentLanguage = StorefrontSessionHelper::getLanguage();

// Get all languages
$languages = LanguageHelper::getAll();
```

## Fallback Support

Lunar automatically falls back to the default language when a translation is missing.

### How It Works

1. **Current Locale**: First, the system tries to get the translation for the current locale
2. **Default Language**: If not found, it falls back to the default language (English)
3. **First Available**: As a last resort, it uses the first available translation

### Example

```php
// Product with only English and French translations
$product->attribute_data = [
    'name' => TranslatedText([
        'en' => 'Headphones',
        'fr' => 'Écouteurs',
    ]),
];

// Current locale is German (de)
$name = $product->translateAttribute('name'); // Returns 'Headphones' (fallback to default)
```

### Custom Fallback Chain

You can specify a custom locale to use:

```php
// Get French, fallback to English, then first available
$name = $product->translateAttribute('name', 'fr'); // Returns 'Écouteurs'

// Try German, falls back to default
$name = $product->translateAttribute('name', 'de'); // Returns 'Headphones'
```

## Seamless Laravel Integration

Lunar's multi-language system integrates seamlessly with Laravel's localization features:

### Locale Setting

The locale is automatically set when a language is selected:

```php
// Language is set automatically via StorefrontSessionHelper
StorefrontSessionHelper::setLanguage('fr');
// App::setLocale('fr') is called automatically
```

### Using Laravel Translation Helpers

```blade
{{-- Uses current locale automatically --}}
{{ __('frontend.product.add_to_cart') }}

{{-- Laravel's trans() helper also works --}}
{{ trans('frontend.nav.products') }}

{{-- Translation with parameters --}}
{{ __('frontend.category_products', ['category' => $categoryName]) }}
```

### Translation File Structure

```
resources/lang/
├── en/
│   └── frontend.php
├── es/
│   └── frontend.php
├── fr/
│   └── frontend.php
├── de/
│   └── frontend.php
└── zh/
    └── frontend.php
```

## Use Cases

### 1. Multilingual Product Catalogs

Create product catalogs with descriptions in multiple languages:

```php
$product = Product::create([
    'product_type_id' => $productType->id,
    'status' => 'published',
    'attribute_data' => collect([
        'name' => AttributeHelper::translatedText([
            'en' => 'Leather Boots',
            'fr' => 'Bottes en cuir',
            'es' => 'Botas de cuero',
        ]),
        'description' => AttributeHelper::translatedText([
            'en' => 'Premium leather boots with...',
            'fr' => 'Bottes en cuir premium avec...',
            'es' => 'Botas de cuero premium con...',
        ]),
    ]),
]);
```

### 2. Translate Blog Posts, Categories, and Collections

```php
// Collection with translations
$collection = Collection::create([
    'collection_group_id' => $group->id,
    'attribute_data' => collect([
        'name' => AttributeHelper::translatedText([
            'en' => 'Summer Collection',
            'fr' => 'Collection d\'été',
            'es' => 'Colección de verano',
        ]),
    ]),
]);

// Category with translations
$category = Category::create([
    'attribute_data' => collect([
        'name' => AttributeHelper::translatedText([
            'en' => 'Electronics',
            'fr' => 'Électronique',
            'es' => 'Electrónica',
        ]),
    ]),
]);
```

### 3. Dynamic Content Based on Language

```blade
@php
    $currentLanguage = \App\Lunar\StorefrontSession\StorefrontSessionHelper::getLanguage();
    $isRtl = in_array($currentLanguage->code, ['ar', 'he', 'fa']);
@endphp

<html lang="{{ $currentLanguage->code }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Content --}}
</html>
```

## Examples

### Complete Product Creation Example

```php
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use App\Lunar\Attributes\AttributeHelper;

// Get product type
$productType = ProductType::first();

// Create multilingual product
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
            'en' => 'High-quality wireless headphones with active noise cancellation.',
            'fr' => 'Écouteurs sans fil de haute qualité avec annulation active du bruit.',
            'es' => 'Auriculares inalámbricos de alta calidad con cancelación activa de ruido.',
            'de' => 'Hochwertige Funkkopfhörer mit aktiver Geräuschunterdrückung.',
            'zh' => '高品质无线耳机，具有主动降噪功能。',
        ]),
    ]),
]);

// Create variant
$variant = ProductVariant::create([
    'product_id' => $product->id,
    'sku' => 'HEAD-001',
    'price' => 19999, // $199.99 in cents
]);

// Display in views
// {{ $product->translateAttribute('name') }} // Automatically uses current locale
```

### Collection with Sub-collections

```php
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

$group = CollectionGroup::first();

$parentCollection = Collection::create([
    'collection_group_id' => $group->id,
    'attribute_data' => collect([
        'name' => AttributeHelper::translatedText([
            'en' => 'Electronics',
            'fr' => 'Électronique',
        ]),
    ]),
]);

$childCollection = Collection::create([
    'collection_group_id' => $group->id,
    'parent_id' => $parentCollection->id,
    'attribute_data' => collect([
        'name' => AttributeHelper::translatedText([
            'en' => 'Headphones',
            'fr' => 'Écouteurs',
        ]),
    ]),
]);
```

### Language Helper Usage

```php
use App\Lunar\Languages\LanguageHelper;

// Get default language
$defaultLanguage = LanguageHelper::getDefault();

// Get all languages
$languages = LanguageHelper::getAll();

// Find language by code
$french = LanguageHelper::findByCode('fr');

// Create new language
$italian = LanguageHelper::create(
    code: 'it',
    name: 'Italian',
    default: false
);

// Check if language exists
if (LanguageHelper::exists('es')) {
    // Spanish is available
}
```

## Best Practices

1. **Always provide default language translations**: Ensure all content has at least a default language (English) translation.

2. **Use fallback wisely**: The automatic fallback to default language ensures users always see content, even if not in their preferred language.

3. **Keep translation files organized**: Use namespaced translation files (e.g., `frontend.*`) to avoid conflicts.

4. **Test all languages**: When adding new content, test how it displays in all available languages.

5. **Use Laravel's translation system for UI**: Use `__()` helper for UI strings and `translateAttribute()` for database content.

6. **SEO considerations**: Consider creating language-specific URLs using Lunar's URL system for better SEO.

## Troubleshooting

### Translation not showing

1. Check if the locale is set correctly: `App::getLocale()`
2. Verify the translation exists in the database for that locale
3. Check if fallback is working (should show default language)

### Language switcher not working

1. Ensure the language routes are registered in `routes/web.php`
2. Check that `LanguageDetectionMiddleware` is registered
3. Verify the language selector component is included in the layout

### Missing translations

1. Always provide at least the default language translation
2. Use the fallback system to show default language content
3. Consider adding missing translations gradually

## Additional Resources

- [Lunar PHP Documentation](https://docs.lunarphp.com/)
- [Laravel Localization](https://laravel.com/docs/localization)
- [Attribute System Guide](./PRODUCT_ATTRIBUTES_SYSTEM.md)
- [Multi-Language Setup](./MULTI_LANGUAGE_SETUP.md)


