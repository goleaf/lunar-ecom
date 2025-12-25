# B2B Contracts & Price Lists System

## Overview

Complete enterprise B2B contracts and price lists system for managing customer-specific pricing, terms, and rules.

## Features

### 1. Contract Model

**Location**: `app/Models/B2BContract.php`

**Fields**:
- `contract_id` - Unique contract identifier
- `customer_id` - Customer/Company reference
- `name` - Contract name
- `description` - Contract description
- `valid_from` / `valid_to` - Validity period
- `currency_id` - Contract currency
- `priority` - Priority (higher = applied first)
- `status` - Draft, Pending Approval, Active, Expired, Cancelled
- `approval_state` - Pending, Approved, Rejected
- `terms_reference` - Reference to terms document
- `meta` - Additional metadata

**Key Methods**:
- `isActive()` - Check if contract is currently active
- `isExpired()` - Check if contract has expired
- `approve(User $approver)` - Approve contract
- `reject(User $rejector)` - Reject contract

### 2. Price Lists

**Location**: `app/Models/PriceList.php`

**Features**:
- Multiple active price lists per contract
- Inheritance from parent price lists
- Versioning support
- Priority-based application

**Fields**:
- `contract_id` - Parent contract
- `parent_id` - Parent price list (for inheritance)
- `version` - Version number
- `is_active` - Active status
- `valid_from` / `valid_to` - Validity period
- `priority` - Priority (higher = applied first)

**Key Methods**:
- `getInheritedPrices()` - Get prices from parent price list
- `isActive()` - Check if price list is active

### 3. Contract Prices

**Location**: `app/Models/ContractPrice.php`

**Pricing Types**:
- **Variant Fixed**: Fixed price per variant
- **Category**: Category-based pricing
- **Margin Based**: Margin-based pricing (percentage or fixed amount)

**Fields**:
- `pricing_type` - Type of pricing
- `product_variant_id` - Variant (for variant_fixed)
- `category_id` - Category (for category pricing)
- `fixed_price` - Fixed price in minor currency units
- `margin_percentage` - Margin percentage
- `margin_amount` - Fixed margin amount
- `quantity_break` - Quantity break point
- `min_quantity` - Minimum order quantity
- `price_floor` - Minimum price
- `price_ceiling` - Maximum price

**Key Methods**:
- `calculatePrice(ProductVariant $variant, int $quantity, ?int $basePrice)` - Calculate price for variant/quantity

### 4. Contract Rules

**Location**: `app/Models/ContractRule.php`

**Rule Types**:
- `price_override` - Override base prices
- `promotion_override` - Override promotions
- `payment_method` - Restrict payment methods
- `shipping` - Shipping rules
- `discount` - Contract-specific discounts

**Features**:
- Conditional rules (based on cart total, categories, quantity)
- Priority-based application
- JSON-based conditions and actions

**Key Methods**:
- `matches(array $context)` - Check if rule matches context

### 5. Advanced B2B Features

#### Credit Limits
**Location**: `app/Models/ContractCreditLimit.php`

- Credit limit management
- Current balance tracking
- Payment terms (Net 7/15/30/60/90, Immediate)
- Available credit calculation

#### Purchase Orders
**Location**: `app/Models/ContractPurchaseOrder.php`

- PO number tracking
- Approval workflow
- Order association
- Status management (Pending, Approved, Rejected, Fulfilled)

#### Company Hierarchies
**Location**: `app/Models/ContractCompanyHierarchy.php`

- Parent-child company relationships
- Contract inheritance
- Relationship types (Subsidiary, Division, Branch)

#### Sales Rep Attribution
**Location**: `app/Models/ContractSalesRep` (via pivot table)

- Multiple sales reps per contract
- Primary sales rep designation
- Commission rate tracking

#### Shared Carts
**Location**: `app/Models/ContractSharedCart.php`

- Shared carts for collaboration
- User access control
- Contract association

### 6. Contract Auditing

**Location**: `app/Models/ContractAudit.php`

**Audit Types**:
- `price_change` - Price change history
- `usage` - Usage tracking
- `margin_analysis` - Margin analysis
- `expiry_alert` - Expiry alerts
- `contract_change` - Contract changes

**Features**:
- Complete audit trail
- Old/new value tracking
- User attribution
- Order association

## Services

### ContractService
**Location**: `app/Services/ContractService.php`

**Key Methods**:
- `getActiveContractsForCustomer(Customer $customer)` - Get active contracts
- `getBestContractForCustomer(Customer $customer)` - Get highest priority contract
- `hasSufficientCredit(Customer $customer, int $orderAmount)` - Check credit
- `getApplicableRules(B2BContract $contract, array $context)` - Get matching rules
- `shouldOverridePromotions(B2BContract $contract)` - Check promotion override
- `getAllowedPaymentMethods(B2BContract $contract)` - Get payment methods
- `getShippingRules(B2BContract $contract)` - Get shipping rules
- `checkExpiringContracts(int $daysAhead)` - Check for expiring contracts

### PriceListService
**Location**: `app/Services/PriceListService.php`

**Key Methods**:
- `getContractPrice(ProductVariant $variant, Customer $customer, int $quantity)` - Get contract price
- `getQuantityBreaks(ProductVariant $variant, Customer $customer)` - Get quantity breaks
- `createPriceList(B2BContract $contract, array $data)` - Create price list
- `addPrice(PriceList $priceList, array $data)` - Add price to list

## Integration

### Cart Pricing Integration
**Location**: `app/Services/CartPricing/Pipeline/ApplyB2BContractStep.php`

The B2B contract pricing is integrated into the cart pricing pipeline:
1. Resolves base price (Step 1)
2. **Applies B2B contract pricing (Step 2)** ← Contract prices override base prices
3. Applies quantity tiers (Step 3)
4. Applies item discounts (Step 4)
5. Applies cart discounts (Step 5)
6. Calculates shipping (Step 6)
7. Calculates tax (Step 7)
8. Applies rounding (Step 8)

**Key Behavior**:
- Contract prices **always override** base prices (even if higher)
- Contract prices override promotions when `promotion_override` rule is active
- Prices are locked during checkout

## Admin Interface

### B2B Contract Resource
**Location**: `app/Filament/Resources/B2BContractResource.php`

**Features**:
- Create, edit, view contracts
- Approve/reject contracts
- Manage price lists (relation manager)
- Manage rules (relation manager)
- Manage credit limits (relation manager)
- Manage sales reps (relation manager)
- Filter by status, approval state, expiry
- Sort by priority

**Pages**:
- List contracts
- Create contract
- View contract
- Edit contract

**Relation Managers**:
- Price Lists
- Rules
- Credit Limits
- Sales Reps

## Database Schema

### Tables Created:
1. `lunar_b2b_contracts` - Main contracts table
2. `lunar_price_lists` - Price lists
3. `lunar_contract_prices` - Contract prices
4. `lunar_contract_rules` - Contract rules
5. `lunar_contract_credit_limits` - Credit limits
6. `lunar_contract_purchase_orders` - Purchase orders
7. `lunar_contract_audits` - Audit logs
8. `lunar_contract_company_hierarchies` - Company hierarchies
9. `lunar_contract_sales_reps` - Sales rep assignments
10. `lunar_contract_shared_carts` - Shared carts

## Usage Examples

### Get Contract Price for Variant
```php
use App\Services\PriceListService;
use Lunar\Models\ProductVariant;
use Lunar\Models\Customer;

$priceListService = app(PriceListService::class);
$customer = Customer::find(1);
$variant = ProductVariant::find(1);

$contractPrice = $priceListService->getContractPrice($variant, $customer, 10);
// Returns: ['price' => 5000, 'price_list_id' => 1, 'contract_id' => 1, 'version' => '1.0']
```

### Check Credit Limit
```php
use App\Services\ContractService;

$contractService = app(ContractService::class);
$hasCredit = $contractService->hasSufficientCredit($customer, 10000); // $100.00
```

### Get Active Contracts
```php
use App\Services\ContractService;

$contractService = app(ContractService::class);
$contracts = $contractService->getActiveContractsForCustomer($customer);
```

### Create Contract
```php
use App\Services\ContractService;

$contractService = app(ContractService::class);
$contract = $contractService->createContract([
    'customer_id' => 1,
    'name' => 'Annual Contract 2025',
    'valid_from' => now(),
    'valid_to' => now()->addYear(),
    'currency_id' => 1,
    'priority' => 10,
]);
```

## Events

- `ContractValidityChanged` - Fired when contract validity changes (triggers cart repricing)

## Policies

**Location**: `app/Policies/B2BContractPolicy.php`

**Permissions**:
- `view_any_b2b_contract`
- `view_b2b_contract`
- `create_b2b_contract`
- `update_b2b_contract`
- `delete_b2b_contract`
- `approve_b2b_contract`
- `reject_b2b_contract`

## Next Steps

1. Run migrations: `php artisan migrate`
2. Set up permissions in your permission system
3. Configure Filament navigation (already configured)
4. Test contract creation and price application
5. Set up expiry alerts (can be scheduled via `checkExpiringContracts()`)

## Notes

- Contract prices override base prices and promotions
- Multiple active price lists are supported per contract
- Price list inheritance allows for hierarchical pricing
- Contract rules can override promotions, payment methods, and shipping
- Complete audit trail for all contract changes
- Credit limits prevent orders exceeding available credit
- Purchase orders support approval workflows
- Company hierarchies enable contract inheritance

