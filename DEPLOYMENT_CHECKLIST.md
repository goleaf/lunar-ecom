# Cart Pricing Engine - Deployment Checklist

## Pre-Deployment Verification

### ✅ Database Migrations
- [ ] Review all 4 migrations:
  - [ ] `2025_12_26_100000_add_pricing_fields_to_cart_lines_table.php`
  - [ ] `2025_12_26_100001_add_pricing_fields_to_carts_table.php`
  - [ ] `2025_12_26_100002_create_map_prices_table.php`
  - [ ] `2025_12_26_100003_create_cart_pricing_snapshots_table.php`
- [ ] Backup database before migration
- [ ] Test migrations on staging environment first

### ✅ Code Verification
- [ ] All 46 PHP files syntax-checked
- [ ] No linter errors
- [ ] All imports resolved
- [ ] All dependencies available

### ✅ Service Registration
- [ ] `CartPricingEngine` registered in `CartServiceProvider`
- [ ] Observers registered in `AppServiceProvider`
- [ ] Event listeners registered in `EventServiceProvider`
- [ ] All service dependencies resolvable

### ✅ Configuration
- [ ] Review `config/lunar/cart.php` pricing settings
- [ ] Set appropriate `price_expiration_hours`
- [ ] Configure `store_snapshots` (true/false)
- [ ] Verify `auto_reprice` setting

### ✅ Routes
- [ ] Verify `/cart/pricing` route exists
- [ ] Test route accessibility
- [ ] Verify route middleware (if any)

## Deployment Steps

### 1. Backup Database
```bash
# Create database backup
php artisan backup:run --only-db
# OR
mysqldump -u user -p database_name > backup_$(date +%Y%m%d).sql
```

### 2. Run Migrations
```bash
# Dry run (check what will happen)
php artisan migrate --pretend

# Run migrations
php artisan migrate

# Verify migrations
php artisan migrate:status
```

### 3. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 4. Verify Installation
```bash
# Check routes
php artisan route:list --name=cart.pricing

# Check service providers
php artisan config:show app.providers | grep CartServiceProvider

# Verify observers
php artisan tinker
>>> Cart::observe(\App\Observers\CartObserver::class);
>>> CartLine::observe(\App\Observers\CartLineObserver::class);
```

## Post-Deployment Testing

### Functional Tests

#### 1. Basic Cart Pricing
- [ ] Create a cart with items
- [ ] Verify prices are calculated
- [ ] Check pricing snapshot is stored
- [ ] Verify line item pricing fields populated

#### 2. Repricing Triggers
- [ ] Change quantity → verify repricing
- [ ] Change variant → verify repricing
- [ ] Apply discount → verify repricing
- [ ] Change address → verify repricing
- [ ] Change currency → verify repricing

#### 3. Price Integrity
- [ ] Test MAP enforcement (if MAP prices set)
- [ ] Test minimum price enforcement
- [ ] Verify price hash generation
- [ ] Test price mismatch detection

#### 4. API Endpoints
- [ ] Test `GET /cart/pricing` endpoint
- [ ] Verify response structure
- [ ] Check audit trail included
- [ ] Verify decimal values present

#### 5. Discount Application
- [ ] Test item-level discounts
- [ ] Test cart-level discounts
- [ ] Verify discount breakdown
- [ ] Check proportional distribution

#### 6. Tax Calculation
- [ ] Verify tax calculation per line item
- [ ] Check tax breakdown structure
- [ ] Verify tax rates applied correctly

#### 7. Shipping Calculation
- [ ] Verify shipping cost calculation
- [ ] Check shipping cost in totals
- [ ] Verify shipping tax (if applicable)

### Performance Tests

- [ ] Test pricing calculation speed
- [ ] Verify no N+1 queries
- [ ] Check memory usage
- [ ] Test with large carts (100+ items)

### Edge Cases

- [ ] Empty cart handling
- [ ] Guest cart (no customer)
- [ ] Cart without customer groups
- [ ] Cart with expired prices
- [ ] Cart with invalid discounts
- [ ] Cart with missing variants

## Monitoring

### Logs to Watch
- [ ] Price calculation errors
- [ ] MAP violations
- [ ] Price mismatch warnings
- [ ] Repricing trigger events

### Metrics to Track
- [ ] Average pricing calculation time
- [ ] Number of repricing events
- [ ] MAP violations count
- [ ] Price mismatch occurrences

## Rollback Plan

If issues occur:

### 1. Rollback Migrations
```bash
php artisan migrate:rollback --step=4
```

### 2. Restore Database Backup
```bash
mysql -u user -p database_name < backup_YYYYMMDD.sql
```

### 3. Revert Code Changes
```bash
git revert <commit-hash>
# OR
git checkout <previous-commit>
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Configuration Checklist

### Required Settings
- [ ] `lunar.cart.pricing.auto_reprice` - Set to `true`
- [ ] `lunar.cart.pricing.enforce_map` - Set based on requirements
- [ ] `lunar.cart.pricing.enforce_minimum_price` - Set to `true`
- [ ] `lunar.cart.pricing.price_expiration_hours` - Set appropriate value (default: 24)
- [ ] `lunar.cart.pricing.enable_price_hash` - Set to `true`
- [ ] `lunar.cart.pricing.store_snapshots` - Set based on audit requirements

### Optional Settings
- [ ] Configure MAP prices for products (if needed)
- [ ] Set up B2B contract integration (if applicable)
- [ ] Configure discount rules
- [ ] Set up tax zones and rates

## Support & Troubleshooting

### Common Issues

#### Prices Not Updating
1. Check `auto_reprice` config
2. Verify observers registered
3. Check event listeners
4. Verify `requires_reprice` flag

#### MAP Not Enforcing
1. Verify MAP prices exist
2. Check `enforce_map` config
3. Verify validity periods
4. Check enforcement level

#### Discounts Not Applying
1. Verify discount active status
2. Check date ranges
3. Verify customer group restrictions
4. Check minimum cart value

### Documentation
- [ ] Review `CART_PRICING_QUICK_REFERENCE.md`
- [ ] Review `CART_PRICING_ENGINE_READY.md`
- [ ] Review `CART_PRICING_ENGINE_FINAL_SUMMARY.md`

## Sign-Off

- [ ] Code reviewed
- [ ] Migrations tested
- [ ] Functional tests passed
- [ ] Performance acceptable
- [ ] Documentation reviewed
- [ ] Team notified
- [ ] Monitoring configured

---

**Deployment Date**: _______________
**Deployed By**: _______________
**Verified By**: _______________

