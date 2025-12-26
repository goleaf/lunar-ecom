# Referral System Documentation

## Overview

A comprehensive referral system that allows users/companies to share referral links/codes, invite new users who receive welcome discounts, and earn rewards based on actions (signup, first purchase, repeat purchases). Admins have full control over rules, eligibility, limits, stacking, validity, audiences, and can view analytics.

## Features

### Core Functionality

1. **Referral Programs**: Create multiple referral programs with different rules and rewards
2. **Referral Codes**: Unique codes/links for each referrer
3. **Rewards System**: Configurable rewards for referrers and referees
4. **Event Tracking**: Track signups, purchases, and other events
5. **Analytics**: Comprehensive analytics dashboard
6. **Admin Control**: Full admin panel for managing programs, codes, and viewing analytics

### Admin Features

- Create and manage referral programs
- Configure eligibility rules (customer groups, specific users, custom conditions)
- Set up referrer rewards (signup, first purchase, repeat purchase)
- Set up referee welcome discounts
- Configure limits (max referrals, max rewards)
- Control stacking rules
- Set validity periods
- View detailed analytics

### User Features

- Get referral links/codes automatically
- Share referral links
- Track referral statistics
- View earned rewards
- Redeem discount codes

## Database Structure

### Tables

1. **referral_programs**: Main programs with rules and configuration
2. **referral_codes**: Individual referral codes/links
3. **referral_events**: Tracked events (signup, purchase, etc.)
4. **referral_rewards**: Issued rewards
5. **referral_analytics**: Aggregated analytics data
6. **referral_tracking**: Individual click/signup/purchase tracking

## API Endpoints

### Authenticated Endpoints

- `GET /api/referrals/my-codes` - Get user's referral codes
- `GET /api/referrals/stats` - Get referral statistics
- `GET /api/referrals/rewards` - Get user's rewards

### Public Endpoints

- `GET /api/referrals/code/{slug}` - Get referral code info
- `POST /api/referrals/track/{slug}` - Track referral link click

## Usage Examples

### Creating a Referral Program

1. Go to Admin Panel → Marketing → Referral Programs
2. Click "Create"
3. Fill in program details:
   - Name: "Summer Referral Program"
   - Handle: "summer2024"
   - Set eligibility rules
   - Configure referrer rewards
   - Configure referee welcome discounts
   - Set limits and validity

### User Getting Their Referral Link

```php
// Via API
GET /api/referrals/my-codes

// Response includes:
{
  "success": true,
  "data": [
    {
      "program_id": 1,
      "program_name": "Summer Referral Program",
      "code": "SUMABC123",
      "slug": "sumabc123",
      "url": "https://example.com/ref/sumabc123",
      "stats": {
        "clicks": 10,
        "signups": 5,
        "purchases": 3,
        "revenue": 150.00
      }
    }
  ]
}
```

### Tracking Referral Links

Referral links are automatically tracked when users visit URLs like:
- `https://yoursite.com/ref/{slug}`
- `https://yoursite.com/?ref={slug}`

The middleware `TrackReferralLink` automatically captures and stores referral information in the session.

### Processing Referrals

The system automatically processes referrals when:
1. **User Registration**: If a user registers after clicking a referral link, a signup event is created
2. **First Purchase**: When a referred user makes their first purchase
3. **Repeat Purchases**: For subsequent purchases (if configured)

## Configuration

### Reward Types

- **Discount Code**: Creates a discount coupon code
- **Credit**: Account credit (can be applied during checkout)
- **Percentage**: Percentage discount
- **Fixed Amount**: Fixed amount discount

### Stacking Modes

- **Non-Stackable**: Only one discount can be applied
- **Stackable**: Multiple discounts can be combined
- **Exclusive**: This discount takes priority over others

### Eligibility Rules

- **Customer Groups**: Restrict to specific customer groups
- **Specific Users**: Allow only specific user IDs
- **Custom Conditions**: JSON-based custom conditions (e.g., min_orders, min_spend)

## Events & Listeners

### Events

- `ReferralCodeClicked`: Fired when a referral link is clicked
- `ReferralSignup`: Fired when a referred user signs up
- `ReferralPurchase`: Fired when a referred user makes a purchase

### Listeners

- `ProcessReferralClick`: Tracks click in database
- `ProcessReferralSignup`: Processes signup and issues rewards
- `ProcessReferralPurchase`: Processes purchase and issues rewards
- `ProcessUserRegistration`: Integrates with Laravel's Registered event
- `ProcessOrderCompletion`: Integrates with checkout completion

## Admin Panel

### Access

Navigate to: Admin Panel → Marketing → Referral Programs

### Features

1. **Program Management**
   - Create, edit, delete programs
   - Configure all program settings
   - View program statistics

2. **Code Management**
   - View all codes for a program
   - Create manual codes
   - Copy referral URLs
   - View code statistics

3. **Event Tracking**
   - View all referral events
   - Filter by type and status
   - See event details

4. **Rewards Management**
   - View all issued rewards
   - Filter by status
   - Track redemption

5. **Analytics Dashboard**
   - View program analytics
   - Filter by date range
   - See conversion rates
   - Track revenue and rewards

## Integration Points

### User Registration

The system automatically detects referral codes from session when users register. Add this to your registration flow:

```php
// The middleware already stores referral_code_id in session
// ProcessUserRegistration listener handles the rest automatically
```

### Checkout

The system automatically processes referral purchases when orders are completed. The `ProcessOrderCompletion` listener handles this.

### Custom Integration

To manually process referrals:

```php
use App\Services\ReferralService;
use App\Models\ReferralCode;

$referralService = app(ReferralService::class);
$code = ReferralCode::where('slug', $slug)->first();

// Process signup
$referralService->processSignup($code, $user, $customer);

// Process purchase
$referralService->processPurchase($code, $order, $user, $customer, $isFirstPurchase);
```

## Analytics

Analytics are aggregated daily, weekly, or monthly and include:

- Clicks
- Signups
- First purchases
- Repeat purchases
- Total orders
- Total revenue
- Rewards issued
- Rewards value
- Conversion rates (click→signup, signup→purchase, overall)

## Best Practices

1. **Program Design**
   - Keep programs focused and clear
   - Set reasonable limits
   - Test eligibility rules before going live

2. **Rewards**
   - Balance referrer and referee rewards
   - Set appropriate validity periods
   - Monitor reward redemption rates

3. **Tracking**
   - Monitor analytics regularly
   - Adjust programs based on performance
   - Track conversion rates

4. **Security**
   - Validate referral codes
   - Prevent self-referrals (unless allowed)
   - Monitor for abuse

## Troubleshooting

### Referral codes not being tracked

- Check that `TrackReferralLink` middleware is registered
- Verify session is working
- Check that referral code exists and is valid

### Rewards not being issued

- Check program is active
- Verify eligibility rules
- Check event processing logs
- Ensure listeners are registered

### Analytics not updating

- Run analytics aggregation manually:
  ```php
  $service = app(ReferralAnalyticsService::class);
  $service->aggregateAnalytics($program, null, now()->subDays(30), now());
  ```

## Future Enhancements

- Email notifications for rewards
- Social sharing integration
- Referral leaderboards
- A/B testing for programs
- Advanced fraud detection
- Multi-level referrals


