# 🌐 Multi-Language Quick Reference

Quick reference guide for using Lunar's multi-language features.

## Basic Usage

### Display Translated Content

```blade
{{-- Product/Collection name (automatic locale) --}}
{{ $product->translateAttribute('name') }}

{{-- Specific language --}}
{{ $product->translateAttribute('name', 'fr') }}

{{-- UI strings --}}
{{ __('storefront.product.add_to_cart') }}
```

### Switch Language

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;

// Switch by code
StorefrontSessionHelper::setLanguage('fr');

// Get current language
$language = StorefrontSessionHelper::getLanguage();
```

### Create Translated Content

```php
use App\Lunar\Attributes\AttributeHelper;

$product->attribute_data = collect([
    'name' => AttributeHelper::translatedText([
        'en' => 'Product Name',
        'fr' => 'Nom du produit',
        'es' => 'Nombre del producto',
    ]),
]);
```

## TranslationService Methods

### Translate with Fallback

```php
use App\Services\TranslationService;

// Automatic fallback
$name = TranslationService::translate($product, 'name');

// Custom locale with fallback
$name = TranslationService::translate($product, 'name', 'fr', 'en');
```

### Check Translation Availability

```php
// Check if translation exists
if (TranslationService::hasTranslation($product, 'name', 'fr')) {
    // French translation exists
}

// Get all available locales
$locales = TranslationService::getAvailableLocales($product, 'name');
// Returns: ['en', 'fr', 'es']

// Get translation info (value, locale used, is_fallback)
$info = TranslationService::translateWithInfo($product, 'name', 'de');
// Returns: ['value' => 'Product Name', 'locale' => 'en', 'is_fallback' => true]
```

### Manage Translations

```php
// Get all translations
$all = TranslationService::getAllTranslations($product, 'name');
// Returns: ['en' => 'Product Name', 'fr' => 'Nom du produit']

// Set translation
TranslationService::setTranslation($product, 'name', 'de', 'Produktname');

// Check if all required locales exist
$hasAll = TranslationService::hasAllTranslations($product, 'name', ['en', 'fr', 'es']);

// Get missing translations
$missing = TranslationService::getMissingTranslations($product, 'name', ['en', 'fr', 'es', 'de']);
// Returns: ['de'] if German translation is missing
```

## Language Helper

```php
use App\Lunar\Languages\LanguageHelper;

// Get default language
$default = LanguageHelper::getDefault();

// Get all languages
$languages = LanguageHelper::getAll();

// Find by code
$french = LanguageHelper::findByCode('fr');

// Check existence
if (LanguageHelper::exists('es')) {
    // Spanish is available
}
```

## Language Switcher API

### Get All Languages

```http
GET /language
```

### Get Current Language

```http
GET /language/current
```

### Switch Language

```http
POST /language/switch
Content-Type: application/json

{
    "language": "fr"
}
```

## Fallback Behavior

1. **Current locale** → Tries the current locale first
2. **Default language** → Falls back to default language (usually English)
3. **First available** → Uses first available translation as last resort

## Best Practices

✅ **Do:**
- Always provide default language translations
- Use `translateAttribute()` in Blade templates
- Use `TranslationService` for complex scenarios
- Test fallback behavior

❌ **Don't:**
- Hardcode language codes in views
- Assume translations exist without checking
- Skip default language translations

## Common Patterns

### Conditional Display Based on Language

```blade
@php
    $currentLang = \App\Lunar\StorefrontSession\StorefrontSessionHelper::getLanguage();
@endphp

@if($currentLang->code === 'ar')
    <div dir="rtl">
        {{-- RTL content --}}
    </div>
@else
    <div dir="ltr">
        {{-- LTR content --}}
    </div>
@endif
```

### Show Fallback Indicator

```php
$info = TranslationService::translateWithInfo($product, 'name');
if ($info['is_fallback']) {
    echo "⚠ Translation not available in your language";
}
```

### Bulk Translation Check

```php
$requiredLocales = ['en', 'fr', 'es', 'de', 'zh'];

foreach ($products as $product) {
    $missing = TranslationService::getMissingTranslations($product, 'name', $requiredLocales);
    if (!empty($missing)) {
        // Log or handle missing translations
    }
}
```

