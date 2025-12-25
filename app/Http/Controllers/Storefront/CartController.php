<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

class CartController extends Controller
{
    /**
     * Display the cart.
     * 
     * See: https://docs.lunarphp.com/1.x/reference/carts
     */
    public function index()
    {
        $cart = CartSession::current();

        // Calculate cart totals to hydrate all price values
        // See: https://docs.lunarphp.com/1.x/reference/carts#hydrating-the-cart-totals
        if ($cart) {
            $cart->calculate();
        }

        return view('storefront.cart.index', compact('cart'));
    }

    /**
     * Add an item to the cart.
     * 
     * See: https://docs.lunarphp.com/1.x/reference/carts#add-a-cart-line
     */
    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:lunar_product_variants,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);

        try {
            // Add item to cart (validates automatically)
            // See: https://docs.lunarphp.com/1.x/reference/carts#validation
            CartSession::add($variant, $request->quantity);

            return redirect()->route('storefront.cart.index')
                ->with('success', 'Item added to cart');
        } catch (\Lunar\Exceptions\Carts\CartException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update a cart line.
     */
    public function update(Request $request, int $lineId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:999',
        ]);

        CartSession::updateLine($lineId, $request->quantity);

        if ($request->quantity == 0) {
            CartSession::remove($lineId);
        }

        return redirect()->route('storefront.cart.index')
            ->with('success', 'Cart updated');
    }

    /**
     * Remove a cart line.
     */
    public function remove(int $lineId)
    {
        CartSession::remove($lineId);

        return redirect()->route('storefront.cart.index')
            ->with('success', 'Item removed from cart');
    }

    /**
     * Clear the cart.
     */
    public function clear()
    {
        CartSession::clear();

        return redirect()->route('storefront.cart.index')
            ->with('success', 'Cart cleared');
    }
}

