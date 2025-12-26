# GDPR Compliance Features

This document describes the GDPR compliance features implemented in the Lunar E-commerce application.

## Overview

The GDPR compliance system provides:
- **Cookie Consent Management**: Track and manage user cookie preferences
- **Data Export**: Allow users to export all their personal data
- **Data Deletion**: Allow users to request deletion of their personal data
- **Data Anonymization**: Allow users to request anonymization of their data
- **Privacy Policy Management**: Version-controlled privacy policies
- **Consent Tracking**: Comprehensive tracking of all user consents

## Database Tables

### cookie_consents
Stores user cookie consent preferences with categories (necessary, analytics, marketing, preferences).

### privacy_policies
Stores versioned privacy policies with effective dates and content.

### gdpr_requests
Tracks GDPR requests (export, deletion, anonymization, rectification) with verification tokens and processing status.

### consent_tracking
Tracks all consent events with timestamps, purposes, and withdrawal information.

## Features

### 1. Cookie Consent Management

**Routes:**
- `GET /gdpr/cookie-consent` - Get current consent status
- `POST /gdpr/cookie-consent` - Store/update consent
- `PUT /gdpr/cookie-consent` - Update consent

**Usage:**
The cookie consent banner appears automatically on first visit. Users can:
- Accept all cookies
- Accept only necessary cookies
- Customize cookie preferences

**Implementation:**
- Cookie consent banner component (`resources/views/gdpr/cookie-consent-banner.blade.php`)
- CookieConsentController handles API requests
- Consent is tracked in both `cookie_consents` and `consent_tracking` tables

### 2. Data Export

**Routes:**
- `GET /gdpr/request/create?type=export` - Show export request form
- `POST /gdpr/request` - Create export request
- `GET /gdpr/request/verify/{token}` - Verify and process request
- `GET /gdpr/request/download/{token}` - Download exported data

**Exported Data Includes:**
- User account information
- Customer profile data
- Order history with details
- Addresses
- Cart history
- Cookie consents
- Consent tracking history
- Reviews (if applicable)
- Search history (if applicable)

**Process:**
1. User submits export request
2. Verification email sent
3. User clicks verification link
4. Data is exported to JSON file
5. User receives download link

### 3. Data Deletion

**Routes:**
- `GET /gdpr/request/create?type=deletion` - Show deletion request form
- `POST /gdpr/request` - Create deletion request

**Process:**
1. User submits deletion request with confirmation
2. Verification email sent
3. User clicks verification link
4. System checks for pending orders
5. If safe, all user data is deleted
6. User account is removed

**Note:** Orders may be preserved for legal compliance but can be anonymized separately.

### 4. Data Anonymization

**Routes:**
- `GET /gdpr/request/create?type=anonymization` - Show anonymization request form
- `POST /gdpr/request` - Create anonymization request

**Process:**
1. User submits anonymization request
2. Verification email sent
3. User clicks verification link
4. Personal data is anonymized:
   - User email → `deleted_{id}_@deleted.local`
   - User name → `Deleted User`
   - Customer data → `Deleted Customer`
   - Addresses → `[Anonymized]`
   - Order addresses → Anonymized
5. Order history preserved but marked as anonymized

### 5. Privacy Policy Management

**Routes:**
- `GET /gdpr/privacy-policy` - View current privacy policy
- `GET /gdpr/privacy-policy/versions` - View all versions
- `GET /gdpr/privacy-policy/version/{version}` - View specific version

**Features:**
- Version control
- Effective dates
- Active/inactive status
- Current policy flag

### 6. Privacy Settings

**Routes:**
- `GET /gdpr/privacy-settings` - View privacy settings (requires auth)
- `PUT /gdpr/privacy-settings` - Update cookie preferences (requires auth)

**Features:**
- Manage cookie preferences
- View consent history
- Access GDPR rights (export, deletion, anonymization)

## Services

### GdprDataExportService
Handles exporting user/customer data to JSON format.

### GdprDataDeletionService
Handles complete deletion of user/customer data with safety checks.

### GdprDataAnonymizationService
Handles anonymization of personal data while preserving business records.

## Models

### CookieConsent
- Tracks cookie preferences
- Methods: `hasConsented()`, `getConsentedCategories()`

### PrivacyPolicy
- Manages privacy policy versions
- Methods: `setAsCurrent()`, scopes: `current()`, `active()`

### GdprRequest
- Tracks GDPR requests
- Methods: `markAsVerified()`, `markAsCompleted()`, `markAsRejected()`, `addLog()`

### ConsentTracking
- Tracks all consent events
- Methods: `recordConsent()`, `hasConsent()`, `withdraw()`

## Frontend Components

### Cookie Consent Banner
- Alpine.js powered
- Customizable cookie categories
- Accept all / necessary only / customize options
- Auto-shows on first visit

### Privacy Settings Page
- Cookie preference toggles
- GDPR rights section
- Consent history table

### Privacy Policy Page
- Version display
- Effective date
- Full policy content

## Usage Examples

### Check Cookie Consent
```php
use App\Models\CookieConsent;

$consent = CookieConsent::where('user_id', $user->id)->latest()->first();
if ($consent && $consent->hasConsented('analytics')) {
    // Load analytics scripts
}
```

### Track Consent
```php
use App\Models\ConsentTracking;

ConsentTracking::recordConsent(
    ConsentTracking::TYPE_MARKETING,
    'Newsletter subscription',
    true,
    $user->id,
    $customer->id,
    session()->getId()
);
```

### Create Privacy Policy
```php
use App\Models\PrivacyPolicy;

$policy = PrivacyPolicy::create([
    'version' => '2.0',
    'title' => 'Privacy Policy v2.0',
    'content' => '...',
    'effective_date' => now(),
    'is_active' => true,
]);
$policy->setAsCurrent();
```

## Email Notifications

The system includes placeholders for email notifications:
- Verification emails for GDPR requests
- Export ready notifications
- Deletion complete notifications
- Anonymization complete notifications

**TODO:** Implement email classes using Laravel Mail.

## Security Considerations

1. **Verification Tokens**: All GDPR requests require email verification
2. **Secure Downloads**: Export files are protected by verification tokens
3. **Audit Trail**: All actions are logged in `processing_log`
4. **IP Tracking**: Requests include IP address and user agent for security

## Legal Compliance

- **Right to Access**: ✅ Data export feature
- **Right to Erasure**: ✅ Data deletion feature
- **Right to Rectification**: ✅ Request type available (requires manual review)
- **Right to Data Portability**: ✅ JSON export format
- **Right to Object**: ✅ Consent withdrawal tracking
- **Consent Management**: ✅ Cookie consent and tracking

## Next Steps

1. Implement email notifications (Mail classes)
2. Add admin panel for managing privacy policies
3. Add admin panel for reviewing GDPR requests
4. Add scheduled job to clean up old export files
5. Add middleware to block analytics/marketing scripts based on consent
6. Add consent withdrawal API endpoints
7. Add data rectification workflow

## Testing

Run migrations:
```bash
php artisan migrate
```

Create a privacy policy:
```php
php artisan tinker
PrivacyPolicy::create([
    'version' => '1.0',
    'title' => 'Privacy Policy',
    'content' => 'Your privacy policy content here...',
    'effective_date' => now(),
    'is_active' => true,
    'is_current' => true,
]);
```

## Support

For questions or issues, refer to:
- GDPR documentation: https://gdpr.eu/
- Laravel documentation: https://laravel.com/docs
- Lunar PHP documentation: https://docs.lunarphp.com/

