# Referral Code Generation & Attribution System

## Overview

This document describes the referral code generation and attribution system with strict priority rules, cookie-based tracking, and admin controls.

## Code Generation

### Rules

Referral codes are generated with the following characteristics:

- **Unique**: Case-insensitive uniqueness check
- **Short**: 6-10 characters (default: 8)
- **Case-insensitive**: Codes are stored uppercase
- **Safe alphabet**: Excludes 0/O and 1/I to prevent confusion
  - Alphabet: `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`

### Usage

```php
use App\Services\ReferralCodeGeneratorService;

$generator = app(ReferralCodeGeneratorService::class);

// Generate default code (8 chars)
$code = $generator->generate();

// Generate custom length
$code = $generator->generate(10);

// Generate with prefix
$code = $generator->generate(8, 'VIP');

// Generate vanity code
$vanityCode = $generator->generateVanityCode('SUMMER', 3); // SUMMERABC

// Reserve vanity code
$reserved = $generator->reserveVanityCode('SPECIAL');

// Regenerate for user
$newCode = $generator->regenerateForUser($user);
```

### User Model Methods

```php
// Generate code if not exists
$user->generateReferralCode();

// Regenerate code
$user->regenerateReferralCode();

// Get referral link
$link = $user->getReferralLink(); // https://yoursite.com/ref/ABC12345
```

## Attribution System

### Priority Order

Attribution follows a strict priority system:

1. **Explicit Code** (Priority 1) - Highest priority
   - User enters referral code during signup
   - Stored in `referral_code` field or request parameter

2. **Cookie-Based** (Priority 2) - Medium priority
   - Referral link clicked and stored in cookie
   - Configurable: first-click or last-click wins

3. **Manual Admin** (Priority 3) - Lowest priority
   - Admin manually assigns attribution
   - Used for support cases

### Attribution Flow

```php
use App\Services\ReferralAttributionService;

$attributionService = app(ReferralAttributionService::class);

// Track click (stores in cookie)
$attributionService->trackClick($referralCode, $referrer, $lastClickWins);

// Create attribution on signup
$attribution = $attributionService->createAttribution(
    $referee,
    $program,
    $explicitCode, // Optional explicit code
    $lastClickWins, // From program config
    $attributionTtlDays // From program config
);

// Manual attribution (admin)
$attribution = $attributionService->createManualAttribution(
    $referee,
    $referrer,
    $program,
    $code,
    $notes
);
```

### Cookie Management

- **Cookie Name**: `referral_code`
- **Default TTL**: 30 days
- **Configurable**: Per-program attribution TTL (7-30 days typical)

The cookie is set when a user clicks a referral link and respects the `last_click_wins` setting:

- **Last Click Wins = true**: New click overwrites existing cookie
- **Last Click Wins = false**: First click is preserved, subsequent clicks ignored

### Attribution TTL

Each program can configure an attribution TTL (Time To Live):

- **Default**: 7 days
- **Range**: 1-365 days
- **Purpose**: How long a referral click remains valid for attribution

If a user signs up after the TTL expires, the attribution is not created.

## Admin Features

### Code Management

**Location**: Admin Panel → Marketing → Referral Codes

**Features**:
- View all users with referral codes
- Generate codes for users without codes
- Regenerate codes for existing users
- Bulk generate codes
- Block/unblock referral rewards
- Copy referral links

**Actions**:
- **Generate Code**: Create code for user without one
- **Regenerate Code**: Create new code (old code becomes invalid)
- **Block Referrals**: Prevent user from earning referral rewards
- **Unblock Referrals**: Allow user to earn referral rewards

### Vanity Code Reservation

**Reserve Codes for VIP Users**:

1. Go to Referral Codes → "Reserve Vanity Code"
2. Enter desired code (must follow safe alphabet rules)
3. Assign to user or reserve for future user (by email)
4. Add notes

Reserved codes are protected from automatic generation and can only be assigned manually.

### Attribution Management

**Location**: Admin Panel → Marketing → Referral Attributions

**Features**:
- View all attributions
- Filter by status, method, program
- Confirm/reject attributions
- Manual attribution creation
- Bulk actions

**Status Workflow**:
1. **Pending**: Initial state, awaiting fraud check
2. **Confirmed**: Attribution approved, rewards can be issued
3. **Rejected**: Attribution denied, with reason

## Configuration

### Program-Level Settings

Each referral program has:

- **last_click_wins** (boolean): Whether last click overwrites previous
- **attribution_ttl_days** (integer): How long clicks remain valid (default: 7)

### Example Configuration

```php
$program = ReferralProgram::create([
    'name' => 'Summer Referral',
    'status' => ReferralProgram::STATUS_ACTIVE,
    'last_click_wins' => true, // Last click overwrites
    'attribution_ttl_days' => 14, // 14-day attribution window
    'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
]);
```

## Integration Points

### User Registration

The `ProcessUserRegistration` listener automatically:

1. Generates referral code for new user
2. Checks for explicit code in request
3. Checks cookie for referral code
4. Creates attribution with proper priority
5. Clears session/cookie after attribution

### Referral Link Click

The `TrackReferralLink` middleware:

1. Detects referral code in URL (`/ref/{code}` or `?ref={code}`)
2. Validates referrer exists and is active
3. Tracks click in database
4. Stores in cookie based on `last_click_wins` setting
5. Stores in session for immediate use

### Signup Form Integration

Add referral code field to signup form:

```html
<input 
    type="text" 
    name="referral_code" 
    placeholder="Referral Code (Optional)"
    maxlength="10"
/>
```

The system will prioritize this explicit code over cookie-based attribution.

## API Endpoints

### Get User's Referral Code

```http
GET /api/referrals/my-codes
Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "data": {
    "code": "ABC12345",
    "slug": "abc12345",
    "link": "https://yoursite.com/ref/abc12345",
    "stats": {
      "clicks": 10,
      "signups": 5,
      "purchases": 3
    }
  }
}
```

### Regenerate Code

```http
POST /api/referrals/regenerate-code
Authorization: Bearer {token}
```

## Validation

### Code Format Validation

```php
$generator = app(ReferralCodeGeneratorService::class);

if ($generator->isValidFormat($code)) {
    // Code is valid format
}

if ($generator->codeExists($code)) {
    // Code already exists
}
```

### Attribution Validation

The system validates attributions before creating:

- ✅ Referrer and referee are different users
- ✅ Neither user is blocked
- ✅ Program is eligible for referee
- ✅ Attribution doesn't already exist within TTL
- ✅ Referrer code is valid

## Best Practices

1. **Code Length**: Use 8 characters for balance of uniqueness and usability
2. **Attribution TTL**: 7-14 days is typical, adjust based on your sales cycle
3. **Last Click Wins**: Enable for most cases, disable if you want to reward first referrer
4. **Vanity Codes**: Reserve short, memorable codes for VIP users/influencers
5. **Monitoring**: Regularly review pending attributions for fraud patterns

## Troubleshooting

### Code Not Generating

- Check user doesn't already have a code
- Verify safe alphabet is being used
- Check for reserved codes conflict

### Attribution Not Created

- Verify program is active and eligible
- Check attribution TTL hasn't expired
- Ensure referrer code is valid
- Check neither user is blocked

### Cookie Not Working

- Verify cookie domain/path settings
- Check browser cookie settings
- Ensure middleware is registered
- Check `last_click_wins` setting

