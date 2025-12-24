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
     */
    public function index()
    {
        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            return redirect()->route('storefront.cart.index')
                ->with('error', 'Your cart is empty');
        }

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

        // Set shipping address
        $cart->shippingAddress()->create($request->shipping_address);

        // Set billing address
        $cart->billingAddress()->create($request->billing_address);

        // Create order
        $order = CartSession::createOrder();

        return redirect()->route('storefront.checkout.confirmation', $order)
            ->with('success', 'Order placed successfully');
    }

    /**
     * Display order confirmation.
     */
    public function confirmation(Order $order)
    {
        return view('storefront.checkout.confirmation', compact('order'));
    }
}

