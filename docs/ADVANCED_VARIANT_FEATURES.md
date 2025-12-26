# Advanced / Optional Variant Features

For serious platforms.

## Overview

Advanced variant features for enterprise-level e-commerce:

1. **Serial number tracking** - Track individual serial numbers
2. **Expiry dates** - Per-variant expiration dates
3. **Lot/batch tracking** - Manufacturing batch tracking
4. **Subscription variants** - Recurring subscription products
5. **Digital-only variants** - Digital products with no shipping
6. **License key management** - Software license keys
7. **Variant personalization fields** - Customization options (engraving, custom text, uploads)

## Features

### Serial Number Tracking

Track individual serial numbers for variants.

**Database:**
- `variant_serial_numbers` table
- Status: available, allocated, sold, returned, damaged

**Usage:**

```php
use App\Services\VariantAdvancedFeaturesService;

$service = app(VariantAdvancedFeaturesService::class);

// Generate serial numbers
$serialNumbers = $service->generateSerialNumbers($variant, 10);

// Allocate to order
$serialNumber = $service->allocateSerialNumber($variant, $orderLineId);

// Check if variant requires serial tracking
$requiresTracking = $service->requiresSerialNumberTracking($variant);
```

**Model Methods:**

```php
// Get serial numbers
$serialNumbers = $variant->serialNumbers;

// Get available serial numbers
$available = $variant->serialNumbers()->available()->get();

// Allocate serial number
$serialNumber = $variant->serialNumbers()->available()->first();
$serialNumber->allocate($orderLineId);
```

### Expiry Dates

Track expiry dates per variant.

**Fields:**
- `expiry_date` - Specific expiry date
- `shelf_life_days` - Days until expiry

**Usage:**

```php
// Set expiry date
$variant->update([
    'expiry_date' => now()->addMonths(6),
    'shelf_life_days' => 180,
]);

// Check if expired
if ($variant->isExpired()) {
    // Handle expired variant
}
```

### Lot/Batch Tracking

Track manufacturing lots/batches.

**Database:**
- `variant_lots` table
- Lot number, batch number, manufacture date, expiry date

**Usage:**

```php
// Create lot
$lot = $service->createLot($variant, [
    'lot_number' => 'LOT-001',
    'batch_number' => 'BATCH-2025-01',
    'manufacture_date' => now(),
    'expiry_date' => now()->addMonths(12),
    'quantity' => 100,
]);

// Allocate from lot
$service->allocateFromLot($lot, 10);

// Get available quantity
$available = $lot->available_quantity;

// Check if expired
if ($lot->isExpired()) {
    // Handle expired lot
}
```

**Model Methods:**

```php
// Get lots
$lots = $variant->lots;

// Get available lots
$availableLots = $variant->lots()
    ->where('expiry_date', '>', now())
    ->whereRaw('quantity > (quantity_allocated + quantity_sold)')
    ->get();
```

### Subscription Variants

Recurring subscription products.

**Fields:**
- `is_subscription` - Is subscription variant
- `subscription_interval` - daily, weekly, monthly, yearly
- `subscription_interval_count` - Interval count
- `subscription_trial_days` - Trial period days

**Usage:**

```php
// Create subscription variant
$variant->update([
    'is_subscription' => true,
    'subscription_interval' => 'monthly',
    'subscription_interval_count' => 1,
    'subscription_trial_days' => 7,
]);

// Check if subscription
if ($variant->isSubscription()) {
    $interval = $service->getSubscriptionInterval($variant);
    // Handle subscription logic
}
```

### Digital-Only Variants

Digital products with no physical shipping.

**Fields:**
- `is_digital` - Is digital variant
- `requires_license_key` - Requires license key

**Usage:**

```php
// Create digital variant
$variant->update([
    'is_digital' => true,
    'requires_license_key' => true,
]);

// Check if digital
if ($variant->isDigital()) {
    // Skip shipping, deliver digitally
}
```

### License Key Management

Manage software license keys.

**Database:**
- `variant_license_keys` table
- `license_key_activations` table

**Usage:**

```php
// Generate license keys
$licenseKeys = $service->generateLicenseKeys($variant, 10, [
    'format' => 'XXXX-XXXX-XXXX-XXXX',
    'expiry_date' => now()->addYear(),
    'max_activations' => 3,
]);

// Allocate to order
$licenseKey = $service->allocateLicenseKey($variant, $orderLineId);

// Activate license
$activation = $licenseKey->activate([
    'user_id' => auth()->id(),
    'device_id' => 'device-123',
    'device_name' => 'My Computer',
]);

// Check if can be activated
if ($licenseKey->canBeActivated()) {
    // Allow activation
}
```

**Model Methods:**

```php
// Get license keys
$licenseKeys = $variant->licenseKeys;

// Get available license keys
$available = $variant->licenseKeys()->available()->get();

// Get activations
$activations = $licenseKey->activations()->where('is_active', true)->get();

// Deactivate
$activation->deactivate();
```

### Variant Personalization Fields

Customization options (engraving, custom text, file uploads).

**Database:**
- `variant_personalizations` table

**Fields:**
- `field_name` - Field identifier
- `field_type` - text, textarea, file, image, select
- `field_value` - Field value or file path
- `field_options` - Options for select fields

**Usage:**

```php
// Define personalization fields
$variant->update([
    'allows_personalization' => true,
    'personalization_fields' => [
        [
            'name' => 'engraving_text',
            'type' => 'text',
            'label' => 'Engraving Text',
            'max_length' => 50,
            'required' => false,
        ],
        [
            'name' => 'custom_message',
            'type' => 'textarea',
            'label' => 'Custom Message',
            'max_length' => 200,
            'required' => false,
        ],
        [
            'name' => 'upload_file',
            'type' => 'file',
            'label' => 'Upload File',
            'accept' => 'image/*',
            'required' => false,
        ],
    ],
]);

// Save personalization data
$service->savePersonalizations($variant, $orderLineId, [
    'engraving_text' => [
        'type' => 'text',
        'value' => 'Happy Birthday!',
    ],
    'custom_message' => [
        'type' => 'textarea',
        'value' => 'Custom message here',
    ],
    'upload_file' => [
        'type' => 'file',
        'value' => '/uploads/file.jpg',
    ],
]);

// Get personalization fields
$fields = $service->getPersonalizationFields($variant);
```

**Model Methods:**

```php
// Get personalizations
$personalizations = $variant->personalizations;

// Check if allows personalization
if ($variant->allowsPersonalization()) {
    // Show personalization form
}
```

## Frontend Integration

### Personalization Form

```blade
@if($variant->allowsPersonalization())
    <div class="personalization-fields">
        @foreach($variant->personalization_fields as $field)
            <div class="field">
                <label>{{ $field['label'] }}</label>
                
                @if($field['type'] === 'text')
                    <input 
                        type="text" 
                        name="personalization[{{ $field['name'] }}]"
                        maxlength="{{ $field['max_length'] ?? 255 }}"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}
                    />
                @elseif($field['type'] === 'textarea')
                    <textarea 
                        name="personalization[{{ $field['name'] }}]"
                        maxlength="{{ $field['max_length'] ?? 1000 }}"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}
                    ></textarea>
                @elseif($field['type'] === 'file')
                    <input 
                        type="file" 
                        name="personalization[{{ $field['name'] }}]"
                        accept="{{ $field['accept'] ?? '*' }}"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}
                    />
                @endif
            </div>
        @endforeach
    </div>
@endif
```

### Subscription Display

```blade
@if($variant->isSubscription())
    <div class="subscription-info">
        <p>Subscription: {{ $variant->subscription_interval_count }} {{ $variant->subscription_interval }}</p>
        @if($variant->subscription_trial_days)
            <p>Trial: {{ $variant->subscription_trial_days }} days</p>
        @endif
    </div>
@endif
```

### Digital Product Delivery

```blade
@if($variant->isDigital())
    <div class="digital-product">
        <p>This is a digital product. No shipping required.</p>
        @if($variant->requires_license_key)
            <p>License key will be provided after purchase.</p>
        @endif
    </div>
@endif
```

## Admin Management

### Serial Number Management

```php
// Generate serial numbers
$service->generateSerialNumbers($variant, 100);

// View serial numbers
$serialNumbers = $variant->serialNumbers()->paginate(50);

// Allocate to order
$serialNumber = $service->allocateSerialNumber($variant, $orderLineId);
```

### Lot Management

```php
// Create lot
$lot = $service->createLot($variant, [
    'lot_number' => 'LOT-2025-001',
    'quantity' => 500,
    'expiry_date' => now()->addYear(),
]);

// View lots
$lots = $variant->lots()->paginate(50);

// Check lot status
foreach ($lots as $lot) {
    echo "Lot: {$lot->lot_number}, Available: {$lot->available_quantity}";
    if ($lot->isExpired()) {
        echo " (EXPIRED)";
    }
}
```

### License Key Management

```php
// Generate license keys
$keys = $service->generateLicenseKeys($variant, 50, [
    'format' => 'XXXX-XXXX-XXXX-XXXX',
    'max_activations' => 3,
]);

// View license keys
$licenseKeys = $variant->licenseKeys()->paginate(50);

// View activations
$activations = $licenseKey->activations()->where('is_active', true)->get();
```

## Best Practices

1. **Serial numbers**: Generate in batches, track allocation
2. **Expiry dates**: Check on product display, warn customers
3. **Lot tracking**: Use FIFO (First In, First Out) for allocation
4. **Subscriptions**: Integrate with payment gateway subscriptions
5. **Digital products**: Skip shipping, deliver immediately
6. **License keys**: Generate unique keys, track activations
7. **Personalization**: Validate input, store securely
8. **Performance**: Index database tables properly
9. **Security**: Protect license keys and serial numbers
10. **Compliance**: Track for regulatory requirements

## Notes

- **Serial numbers**: Required for warranty, returns, recalls
- **Expiry dates**: Important for food, medicine, chemicals
- **Lot tracking**: Required for manufacturing, quality control
- **Subscriptions**: Integrate with Stripe, PayPal subscriptions
- **Digital products**: No shipping costs, instant delivery
- **License keys**: Unique per purchase, activation limits
- **Personalization**: Customer-specific customization


