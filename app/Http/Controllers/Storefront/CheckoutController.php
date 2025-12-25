<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Models\Order;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page.
     * 
     * See: https://docs.lunarphp.com/1.x/reference/carts
     */
    public function index()
    {
        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            return redirect()->route('storefront.cart.index')
                ->with('error', 'Your cart is empty');
        }

        // Calculate cart totals to show accurate pricing
        // See: https://docs.lunarphp.com/1.x/reference/carts#hydrating-the-cart-totals
        $cart->calculate();

        return view('storefront.checkout.index', compact('cart'));
    }

    /**
     * Store the order (process checkout).
     */
    public function store(Request $request)
    {
        $request->validate([
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.line_one' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'nullable|string|max:255',
            'shipping_address.postcode' => 'required|string|max:255',
            'shipping_address.country_id' => 'required|exists:lunar_countries,id',
            'billing_address.first_name' => 'required|string|max:255',
            'billing_address.last_name' => 'required|string|max:255',
            'billing_address.line_one' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:255',
            'billing_address.state' => 'nullable|string|max:255',
            'billing_address.postcode' => 'required|string|max:255',
            'billing_address.country_id' => 'required|exists:lunar_countries,id',
        ]);

        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            return redirect()->route('storefront.cart.index')
                ->with('error', 'Your cart is empty');
        }

        // Set shipping and billing addresses on cart
        // See: https://docs.lunarphp.com/1.x/reference/carts#adding-shippingbilling-address
        $cart->setShippingAddress($request->shipping_address);
        $cart->setBillingAddress($request->billing_address);

        // Recalculate cart with addresses for accurate tax calculation
        // See: https://docs.lunarphp.com/1.x/reference/carts#calculating-tax
        $cart->calculate();

        // Validate cart before creating order
        // See: https://docs.lunarphp.com/1.x/reference/orders#validating-a-cart-before-creation
        if (!$cart->canCreateOrder()) {
            return redirect()->route('storefront.cart.index')
                ->with('error', 'Cart is not ready to create an order. Please review your cart.');
        }

        // Create order from cart (recommended method)
        // See: https://docs.lunarphp.com/1.x/reference/orders#create-an-order
        $order = CartSession::createOrder();

        return redirect()->route('storefront.checkout.confirmation', $order)
            ->with('success', 'Order placed successfully');
    }

    /**
     * Display order confirmation.
     */
    public function confirmation(Order $order)
    {
        // Ensure user can view this order (owns the order)
        $this->authorize('view', $order);
        
        return view('storefront.checkout.confirmation', compact('order'));
    }
}

