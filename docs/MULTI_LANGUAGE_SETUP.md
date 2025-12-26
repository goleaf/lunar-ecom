# Multi-Language Setup

This document describes the multi-language setup for the Lunar e-commerce application.

## Overview

The application supports multiple languages (English, Spanish, French, German, Chinese) with:
- Language detection (URL parameter, browser header, session)
- Translation files for UI strings
- Database translations for products, categories, and collections
- Frontend language switcher
- Automatic locale setting

## Setup

### 1. Seed Languages

Run the language seeder to create and configure all languages:

```bash
php artisan languages:seed
```

Or use the seeder directly:

```bash
php artisan db:seed --class=LanguageSeeder
```

### 2. Configured Languages

The following languages are configured:

| Code | Name | Default |
|------|------|---------|
| en   | English | Yes |
| es   | Spanish | No |
| fr   | French | No |
| de   | German | No |
| zh   | Chinese | No |

## Usage

### Backend

#### Get Current Language

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;

$language = StorefrontSessionHelper::getLanguage();
```

#### Switch Language

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;

// By code
StorefrontSessionHelper::setLanguage('fr');

// By language instance
$language = LanguageHelper::findByCode('de');
StorefrontSessionHelper::setLanguage($language);
```

#### Get All Languages

```php
use App\Lunar\Languages\LanguageHelper;

$languages = LanguageHelper::getAll();
```

#### Using Translations in Views

```blade
{{ __('frontend.nav.products') }}
{{ __('frontend.cart.title') }}
{{ __('frontend.product.add_to_cart') }}
```

#### Product/Collection Translations

Products and collections use Lunar's `translateAttribute()` method which automatically uses the current locale:

```blade
{{ $product->translateAttribute('name') }}
{{ $product->translateAttribute('description') }}
{{ $collection->translateAttribute('name') }}
```

The `translateAttribute()` method:
- Uses the current locale (set via `App::setLocale()`)
- Falls back to the default language if translation is not available
- Works automatically with Lunar's attribute system

### Frontend

#### Language Selector

The language selector is automatically included in the navigation bar. Users can:
- View all available languages
- See the current language
- Switch languages with a single click
- See the page automatically reload with new translations

#### API Endpoints

**Get all languages:**
```
GET /language
```

**Get current language:**
```
GET /language/current
```

**Switch language:**
```
POST /language/switch
Content-Type: application/json

{
    "language": "fr"
}
```

## How It Works

1. **Language Detection**: The `LanguageDetectionMiddleware` detects the language from:
   - URL parameter (`?lang=fr`)
   - Browser `Accept-Language` header
   - Session (if already set)
   - Default language (English)

2. **Language Initialization**: The `StorefrontSessionMiddleware` initializes the language on every request, ensuring the locale is set correctly.

3. **Translation Display**: 
   - UI strings use Laravel's `__()` helper with translation files
   - Product/Collection data uses Lunar's `translateAttribute()` which automatically uses the current locale
   - All translations fall back to the default language if not available

4. **Language Switching**: When a user switches language:
   - The frontend sends a POST request to `/language/switch`
   - The `LanguageController` sets the language in the session and updates the app locale
   - The page reloads to show all content in the new language

## Translation Files

Translation files are located in `resources/lang/{locale}/frontend.php`:

- `resources/lang/en/frontend.php` - English
- `resources/lang/es/frontend.php` - Spanish
- `resources/lang/fr/frontend.php` - French
- `resources/lang/de/frontend.php` - German
- `resources/lang/zh/frontend.php` - Chinese

### Adding New Translations

To add a new translation key:

1. Add the key to all language files in `resources/lang/{locale}/frontend.php`
2. Use it in views: `{{ __('frontend.your.key') }}`

Example:

```php
// resources/lang/en/frontend.php
return [
    'your' => [
        'key' => 'Your English Text',
    ],
];

// resources/lang/fr/frontend.php
return [
    'your' => [
        'key' => 'Votre Texte Français',
    ],
];
```

## Database Translations

Products, collections, and other Lunar models support translations through the `attribute_data` field. When creating products with translations:

```php
use Lunar\Models\Product;
use Lunar\FieldTypes\TranslatedText;
use Lunar\FieldTypes\Text;

$product = Product::create([
    'product_type_id' => $productType->id,
    'status' => 'published',
    'attribute_data' => collect([
        'name' => new TranslatedText(collect([
            'en' => new Text('Premium Headphones'),
            'fr' => new Text('Écouteurs Premium'),
            'es' => new Text('Auriculares Premium'),
            'de' => new Text('Premium-Kopfhörer'),
            'zh' => new Text('高级耳机'),
        ])),
        'description' => new TranslatedText(collect([
            'en' => new Text('High-quality wireless headphones.'),
            'fr' => new Text('Écouteurs sans fil de haute qualité.'),
            // ... other languages
        ])),
    ]),
]);
```

When displaying, use `translateAttribute()` which automatically uses the current locale:

```blade
{{ $product->translateAttribute('name') }}
{{ $product->translateAttribute('description') }}
```

## Files Created/Modified

### New Files
- `database/seeders/LanguageSeeder.php` - Language seeder
- `app/Http/Controllers/Frontend/LanguageController.php` - Language API controller
- `app/Http/Middleware/LanguageDetectionMiddleware.php` - Language detection middleware
- `resources/views/frontend/components/language-selector.blade.php` - Frontend language selector
- `resources/lang/{locale}/frontend.php` - Translation files for 5 languages
- `app/Console/Commands/SeedLanguages.php` - Artisan command for seeding languages

### Modified Files
- `app/Lunar/StorefrontSession/StorefrontSessionHelper.php` - Added language support
- `app/Http/Middleware/StorefrontSessionMiddleware.php` - Added language initialization
- `routes/web.php` - Added language routes
- `resources/views/frontend/layout.blade.php` - Added language selector and translation helpers
- `bootstrap/app.php` - Registered language detection middleware

## Language Detection Priority

The language detection middleware checks in this order:

1. **URL Parameter** (`?lang=fr`) - Highest priority
2. **Browser Header** (`Accept-Language`) - Detects from browser preferences
3. **Session** - Previously selected language
4. **Default Language** - Falls back to English

## Notes

- The default language (English) is always available
- All languages are enabled by default (no enable/disable field)
- Product and collection translations are stored in the database using Lunar's attribute system
- UI translations are stored in Laravel translation files
- The language selector uses Alpine.js for interactivity
- Language changes persist in the session across requests
- The locale is automatically set via `App::setLocale()` when language is switched


