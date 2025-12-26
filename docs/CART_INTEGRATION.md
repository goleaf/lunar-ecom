# 🛒 Lunar PHP Cart Integration - Complete Guide

## ✅ Integration Status

The Lunar PHP Cart has been **fully integrated** into your Laravel application with robust functionality for product management, discounts, taxes, currencies, and more!

## 🎯 Features Implemented

### 1. **Core Cart Functionality** ✅
- ✅ Add products to cart
- ✅ Update cart line quantities
- ✅ Remove items from cart
- ✅ Clear entire cart
- ✅ Cart persistence across sessions
- ✅ Cart merging on user login
- ✅ Cart clearing on logout (configurable)

### 2. **Discount & Coupon System** ✅
- ✅ Apply discount codes/coupons
- ✅ Remove applied discounts
- ✅ Visual discount display in cart
- ✅ Discount validation
- ✅ Discount total calculation

### 3. **AJAX Support** ✅
- ✅ AJAX add to cart (no page reload)
- ✅ AJAX cart updates
- ✅ AJAX cart removal
- ✅ Real-time cart count updates
- ✅ Cart summary API endpoint

### 4. **UI Components** ✅
- ✅ Cart widget in header navigation
- ✅ Real-time cart item count badge
- ✅ Enhanced cart view with discount section
- ✅ Responsive cart table
- ✅ Success/error message handling

### 5. **Integration Points** ✅
- ✅ User authentication integration
- ✅ Cart merging on login
- ✅ Customer association
- ✅ Currency support
- ✅ Channel support
- ✅ Tax calculation
- ✅ Shipping calculation

## 📁 File Structure

### Controllers
- `app/Http/Controllers/Storefront/CartController.php` - Main cart controller with all operations
- `app/Http/Controllers/Storefront/CheckoutController.php` - Checkout processing

### Services
- `app/Services/CartManager.php` - Cart business logic
- `app/Services/CartSessionService.php` - Cart session management

### Contracts
- `app/Contracts/CartManagerInterface.php` - Cart manager interface
- `app/Contracts/CartSessionInterface.php` - Cart session interface

### Listeners
- `app/Listeners/MergeCartOnLogin.php` - Merges guest cart with user cart on login
- `app/Listeners/ClearCartOnLogout.php` - Clears cart on logout (if configured)

### Views
- `resources/views/storefront/cart/index.blade.php` - Cart page view
- `resources/views/storefront/components/cart-widget.blade.php` - Header cart widget

### Configuration
- `config/lunar/cart.php` - Lunar cart configuration

## 🚀 Usage Examples

### Adding Items to Cart

#### Via Form (Traditional)
```blade
<form action="{{ route('frontend.cart.add') }}" method="POST">
    @csrf
    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
    <input type="number" name="quantity" value="1" min="1">
    <button type="submit">Add to Cart</button>
</form>
```

#### Via AJAX (Recommended)
The product page already includes AJAX support. Just use the form and it will automatically handle AJAX requests.

### Updating Cart Quantities

```blade
<form action="{{ route('frontend.cart.update', $lineId) }}" method="POST" class="cart-update-form">
    @csrf
    @method('PUT')
    <input type="number" name="quantity" value="{{ $quantity }}" min="0" max="999">
    <button type="submit">Update</button>
</form>
```

### Applying Discount Codes

```blade
<form action="{{ route('frontend.cart.discount.apply') }}" method="POST" class="discount-form">
    @csrf
    <input type="text" name="coupon_code" placeholder="Enter coupon code" required>
    <button type="submit">Apply</button>
</form>
```

### Removing Discounts

```blade
<form action="{{ route('frontend.cart.discount.remove') }}" method="POST">
    @csrf
    <button type="submit">Remove Discount</button>
</form>
```

### Getting Cart Summary (AJAX)

```javascript
fetch('{{ route("frontend.cart.summary") }}', {
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }
})
.then(response => response.json())
.then(data => {
    console.log('Cart item count:', data.cart.item_count);
    console.log('Cart total:', data.cart.total);
});
```

## 🔧 API Endpoints

### Cart Operations

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/cart` | Display cart page |
| GET | `/cart/summary` | Get cart summary (JSON) |
| POST | `/cart/add` | Add item to cart |
| PUT | `/cart/{lineId}` | Update cart line quantity |
| DELETE | `/cart/{lineId}` | Remove cart line |
| DELETE | `/cart` | Clear entire cart |

### Discount Operations

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/cart/discount/apply` | Apply discount code |
| POST | `/cart/discount/remove` | Remove applied discount |

## 🎨 Cart Widget

The cart widget is automatically included in the header navigation. It shows:
- Cart icon
- Item count badge (updates in real-time)
- Link to cart page

The widget automatically updates when:
- Items are added to cart
- Cart is updated
- Page is loaded

## 💡 Advanced Usage

### Using CartManager Service

```php
use App\Contracts\CartManagerInterface;

class YourController extends Controller
{
    public function __construct(
        protected CartManagerInterface $cartManager
    ) {}

    public function someMethod()
    {
        // Add item
        $cartLine = $this->cartManager->addItem($variant, 2);
        
        // Update quantity
        $this->cartManager->updateQuantity($lineId, 5);
        
        // Apply discount
        $this->cartManager->applyDiscount('SAVE20');
        
        // Get cart info
        $itemCount = $this->cartManager->getItemCount();
        $hasItems = $this->cartManager->hasItems();
        $total = $this->cartManager->getTotal();
    }
}
```

### Using CartSessionService

```php
use App\Services\CartSessionService;

class YourController extends Controller
{
    public function __construct(
        protected CartSessionService $cartSession
    ) {}

    public function someMethod()
    {
        // Get current cart
        $cart = $this->cartSession->current();
        
        // Get or create cart
        $cart = $this->cartSession->getOrCreate();
        
        // Associate with user
        $this->cartSession->associate($user);
        
        // Merge carts on login
        $this->cartSession->mergeOnAuth($user);
    }
}
```

## 🔐 Security Features

- ✅ Authorization checks on product variants
- ✅ CSRF protection on all forms
- ✅ Input validation
- ✅ Stock validation
- ✅ Quantity limits

## 📊 Cart Calculation Pipeline

Lunar automatically calculates:
- ✅ Line totals
- ✅ Subtotal
- ✅ Shipping costs
- ✅ Tax amounts
- ✅ Discount amounts
- ✅ Final total

The calculation pipeline is configured in `config/lunar/cart.php` and includes:
- CalculateLines
- ApplyShipping
- ApplyDiscounts
- CalculateTax
- Calculate (final total)

## 🌍 Multi-Currency Support

The cart automatically uses the current session currency. Currency switching is handled by the `CurrencyController`.

## 🔄 Cart Persistence

- **Guest carts**: Stored in session
- **User carts**: Stored in database and associated with user
- **Cart merging**: Guest cart merges with user cart on login (configurable)

## 📝 Configuration

### Cart Authentication Policy

In `config/lunar/cart.php`:
```php
'auth_policy' => 'merge', // or 'override'
```

### Cart Session Settings

Cart session key and behavior can be configured in Lunar's cart session config.

## 🧪 Testing

Test files are available at:
- `tests/Feature/CartOperationsTest.php`

## 🐛 Troubleshooting

### Cart not persisting
- Check session configuration
- Verify database migrations are run
- Check cart session service configuration

### Discounts not applying
- Verify discount is active in Lunar admin
- Check discount dates (start/end)
- Verify discount conditions are met

### AJAX not working
- Check browser console for errors
- Verify CSRF token is included
- Check Accept headers in requests

## 📚 Additional Resources

- [Lunar PHP Documentation](https://docs.lunarphp.com)
- [Lunar Cart Reference](https://docs.lunarphp.com/1.x/reference/carts)
- [Lunar Discounts](https://docs.lunarphp.com/1.x/reference/discounts)

## ✨ Next Steps

Consider implementing:
- [ ] Saved carts / wishlists
- [ ] Cart abandonment emails
- [ ] Cart expiration
- [ ] Bulk operations
- [ ] Cart sharing
- [ ] Reorder functionality

---

**Integration completed successfully!** 🎉

Your Lunar cart is now fully functional with all the features you need for a robust e-commerce experience.


