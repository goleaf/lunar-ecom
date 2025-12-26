# Referral System Admin Panel

## Overview

A comprehensive admin panel for managing the referral system with maximum dynamic control, including program configuration, rule building, user/group overrides, moderation, and analytics.

## Features

### 1. Referral Programs Section

**Location**: Marketing → Referral Programs

**Capabilities**:
- **Create/Edit Programs**: Full CRUD operations with status management (Draft → Active → Paused → Archived)
- **Time Configuration**: Start/end date & time with timezone support
- **Channel & Currency Scope**: 
  - Select specific channels or apply to all
  - Currency scope (all currencies or specific ones)
- **Attribution Model**: 
  - First-click vs Last-click wins toggle
  - Attribution TTL (days)
  - Code validity period
- **Global Caps & Stacking Policy**:
  - Default stacking mode (Exclusive/Best-of/Stackable)
  - Max total discount percentage
  - Max total discount amount
  - Apply before/after tax
  - Shipping discount stacking
- **Fraud Policy**: Select or create fraud prevention policies
- **User Group Access**: 
  - Audience scope (All/Specific Users/User Groups)
  - Enable/disable per user group

**Navigation**:
- List view with filters (status, date range, expiring soon)
- View program details
- Edit program settings
- Analytics dashboard (dedicated page)
- Quick actions (activate, pause, archive)

### 2. Rules Builder UI

**Location**: Referral Programs → Edit → Rules Tab

**Features**:
- **Drag-and-Drop Priority**: Reorderable rules table with priority-based sorting
- **Trigger Configuration**:
  - Signup
  - First Order Paid
  - Nth Order Paid (with order number input)
  - Subscription Started
- **Conditions**:
  - Min order total
  - Eligible products/categories/collections
  - Currency restrictions
  - Customer group restrictions
- **Rewards Configuration**:
  - **Referee Rewards**: Coupon, Percentage Discount, Fixed Discount, Free Shipping, Store Credit
  - **Referrer Rewards**: Coupon, Store Credit, Percentage Discount (Next Order), Fixed Amount
  - Tiered rewards support (e.g., 1st referral = €5, 5th = €10)
- **Limits**:
  - Max total redemptions
  - Max per referrer
  - Max per referee
  - Cooldown days
  - Validation window (days)
- **Stacking & Priority**:
  - Stacking mode (Exclusive/Best-of/Stackable)
  - Max discount caps (percentage/amount)
  - Apply before tax
  - Shipping discount stacking
  - Priority ordering
- **Test Button**: Simulate rule application with test user and order total

**Table Features**:
- Color-coded trigger events
- Priority badges
- Active/inactive toggle
- Bulk actions (activate/deactivate)
- Filters by trigger event and status

### 3. User & Group Overrides

#### User Overrides

**Location**: Marketing → User Overrides

**Capabilities**:
- **User Selection**: Search by email/name
- **Scope**: 
  - Apply to all programs or specific program
  - Apply to all rules or specific rule
- **Overrides**:
  - Reward value override
  - Stacking mode override
  - Max redemptions override
  - Block referrals toggle
  - Manual VIP tier assignment
- **Bulk Actions**: Unblock referrals for multiple users

#### Group Overrides

**Location**: Marketing → Group Overrides

**Capabilities**:
- **Group Selection**: Select user group
- **Scope**: Same as user overrides (all/specific programs/rules)
- **Overrides**:
  - Reward value override
  - Stacking mode override
  - Max redemptions override
  - Enable/disable program for group
  - Auto VIP tiers (JSON: {"5": "VIP", "10": "Premium"})

### 4. Review & Moderation

**Location**: Marketing → Referral Attributions

**Features**:
- **Attribution List**: 
  - Status badges (Pending/Confirmed/Rejected)
  - Referrer and referee information
  - Attribution method (code/link/manual)
  - Fraud flags indicator
- **Fraud Detection**:
  - Same IP address detection
  - Same email domain detection
  - Visual fraud flag icon with tooltip
- **Moderation Actions**:
  - **Approve**: Confirm attribution and allow rewards
  - **Reject**: Reject attribution with reason
  - **Reverse Reward**: Reverse issued rewards with reason
- **Bulk Actions**:
  - Approve selected
  - Reject selected (with reason)
- **Filters**:
  - Status (Pending/Confirmed/Rejected)
  - Program
  - Referrer/Referee
  - Date range
  - Fraud flags

### 5. Analytics Dashboard

**Location**: Referral Programs → View → Analytics Tab

**Metrics**:
- **Funnel Metrics**:
  - Total Clicks
  - Signups (with click-to-signup conversion rate)
  - First Purchases (with signup-to-purchase conversion rate)
  - Overall Conversion Rate (click-to-purchase)
- **Financial Metrics**:
  - Revenue (from referred orders)
  - Cost (total rewards issued)
  - ROI (Return on Investment percentage)
- **Conversion Funnel**: Visual funnel chart showing:
  - Clicks → Signups → First Purchases
  - Percentage conversion at each stage
  - Last 30 days data
- **Top Referrers Table**:
  - User email
  - Referral count
  - Revenue generated
  - Sorted by referral count

**Visualizations**:
- Stats cards with color-coded metrics
- Funnel chart with progress bars
- Top referrers table
- Conversion rate indicators

## Database Schema

### New Tables

1. **referral_user_overrides**: User-level overrides
   - `user_id`, `referral_program_id`, `referral_rule_id`
   - `reward_value_override`, `stacking_mode_override`, `max_redemptions_override`
   - `block_referrals`, `vip_tier`, `metadata`

2. **referral_group_overrides**: Group-level overrides
   - `user_group_id`, `referral_program_id`, `referral_rule_id`
   - `reward_value_override`, `stacking_mode_override`, `max_redemptions_override`
   - `enabled`, `auto_vip_tiers`, `metadata`

## Admin Resources

### ReferralProgramResource
- **Pages**: List, Create, View, Edit, Analytics
- **Relations**: Rules, Attributions
- **Actions**: View Analytics, Edit, Delete
- **Filters**: Status, Date Range, Expiring Soon

### ReferralRulesRelationManager
- **Features**: Drag-and-drop priority, Test Rule action
- **Bulk Actions**: Activate/Deactivate selected
- **Filters**: Trigger Event, Active Status

### ReferralAttributionResource
- **Pages**: List, Create, View, Edit
- **Actions**: Approve, Reject, Reverse Reward
- **Bulk Actions**: Approve/Reject Selected
- **Filters**: Status, Program, User, Date, Fraud Flags

### ReferralUserOverrideResource
- **Pages**: List, Create, Edit
- **Bulk Actions**: Unblock Referrals
- **Filters**: User, Program, Blocked Status, VIP Tier

### ReferralGroupOverrideResource
- **Pages**: List, Create, Edit
- **Filters**: Group, Program, Enabled Status

## Usage Examples

### Creating a Referral Program

1. Navigate to **Marketing → Referral Programs**
2. Click **Create**
3. Fill in program details:
   - Name: "Summer Referral Campaign"
   - Status: Draft
   - Start Date: 2025-06-01
   - End Date: 2025-08-31
4. Configure channels/currencies
5. Set attribution model (Last-click wins, 7-day TTL)
6. Set global stacking policy (Stackable, max 20%)
7. Select fraud policy
8. Enable for specific user groups
9. Save and activate

### Building a Rule

1. Open program → **Rules** tab
2. Click **Create**
3. Select trigger: **First Order Paid**
4. Configure referee reward: **10% Discount**
5. Configure referrer reward: **€5 Store Credit**
6. Set conditions:
   - Min order total: €50
   - Eligible categories: Electronics
7. Set limits:
   - Max per referrer: 10
   - Validation window: 30 days
8. Set stacking: **Exclusive**
9. Set priority: **10** (high priority)
10. Test rule with sample user/order
11. Save and activate

### Moderating Attributions

1. Navigate to **Marketing → Referral Attributions**
2. Filter by **Pending** status
3. Review fraud flags (red icon indicates potential fraud)
4. Click **Approve** for legitimate attributions
5. Click **Reject** for fraudulent ones (enter reason)
6. Use bulk actions for multiple attributions

### Setting User Overrides

1. Navigate to **Marketing → User Overrides**
2. Click **Create**
3. Select user (search by email)
4. Select program (or leave empty for all)
5. Set overrides:
   - Reward value: €15 (override default €10)
   - Stacking mode: Exclusive
   - Max redemptions: 20
6. Set VIP tier: **Gold**
7. Save

### Viewing Analytics

1. Open program → **Analytics** tab
2. View funnel metrics:
   - Clicks: 1,000
   - Signups: 150 (15% conversion)
   - First Purchases: 75 (50% conversion)
   - Overall: 7.5% click-to-purchase
3. View financial metrics:
   - Revenue: €15,000
   - Cost: €1,500
   - ROI: 900%
4. Review top referrers table
5. Analyze conversion funnel chart

## Best Practices

1. **Program Setup**:
   - Start with Draft status for testing
   - Set clear start/end dates
   - Configure fraud policies before going live
   - Test rules before activating

2. **Rule Building**:
   - Use priority to control rule order
   - Set appropriate limits to prevent abuse
   - Test rules before activation
   - Use tiered rewards for engagement

3. **Moderation**:
   - Review pending attributions daily
   - Check fraud flags carefully
   - Document rejection reasons
   - Monitor fraud rate in analytics

4. **Overrides**:
   - Use sparingly (prefer rule configuration)
   - Document why overrides are needed
   - Review override effectiveness regularly
   - Use group overrides for bulk changes

5. **Analytics**:
   - Monitor conversion funnel regularly
   - Track ROI to optimize program
   - Identify top referrers for VIP treatment
   - Adjust rules based on performance data

## Technical Notes

- **Drag-and-Drop**: Uses Filament's built-in `reorderable()` method
- **Fraud Detection**: Checks IP addresses and email domains
- **Analytics**: Calculates metrics from referral clicks, attributions, and reward issuances
- **Relationships**: All models have proper Eloquent relationships
- **Performance**: Analytics queries use eager loading and aggregation

## Future Enhancements

- Export analytics data to CSV/Excel
- Email notifications for fraud flags
- Automated VIP tier assignment based on referral count
- A/B testing for different rule configurations
- Real-time analytics dashboard
- Custom date range selection in analytics
- Channel/group breakdown in analytics


