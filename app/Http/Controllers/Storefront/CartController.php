<?php

namespace App\Http\Controllers\Storefront;

use App\Contracts\CartManagerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

class CartController extends Controller
{
    public function __construct(
        protected CartManagerInterface $cartManager
    ) {}

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

        // Ensure user can view this variant (can only add purchasable variants to cart)
        $this->authorize('view', $variant);

        try {
            // Add item to cart (validates automatically)
            // See: https://docs.lunarphp.com/1.x/reference/carts#validation
            CartSession::add($variant, $request->quantity);

            if ($request->expectsJson()) {
                $cart = CartSession::current();
                $cart?->calculate();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Item added to cart',
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'total' => $cart?->total?->formatted,
                    ],
                ]);
            }

            return redirect()->route('storefront.cart.index')
                ->with('success', 'Item added to cart');
        } catch (\Lunar\Exceptions\Carts\CartException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

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

        try {
            CartSession::updateLine($lineId, $request->quantity);

            if ($request->quantity == 0) {
                CartSession::remove($lineId);
            }

            if ($request->expectsJson()) {
                $cart = CartSession::current();
                $cart?->calculate();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cart updated',
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'total' => $cart?->total?->formatted,
                    ],
                ]);
            }

            return redirect()->route('storefront.cart.index')
                ->with('success', 'Cart updated');
        } catch (\Lunar\Exceptions\Carts\CartException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a cart line.
     */
    public function remove(Request $request, int $lineId)
    {
        try {
            CartSession::remove($lineId);

            if ($request->expectsJson()) {
                $cart = CartSession::current();
                $cart?->calculate();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'total' => $cart?->total?->formatted,
                    ],
                ]);
            }

            return redirect()->route('storefront.cart.index')
                ->with('success', 'Item removed from cart');
        } catch (\Lunar\Exceptions\Carts\CartException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Clear the cart.
     */
    public function clear(Request $request)
    {
        CartSession::clear();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared',
                'cart' => [
                    'item_count' => 0,
                    'total' => null,
                ],
            ]);
        }

        return redirect()->route('storefront.cart.index')
            ->with('success', 'Cart cleared');
    }

    /**
     * Apply a discount/coupon code to the cart.
     */
    public function applyDiscount(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:255',
        ]);

        try {
            $this->cartManager->applyDiscount($request->coupon_code);

            $cart = CartSession::current();
            $cart?->calculate();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Discount applied successfully',
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'total' => $cart?->total?->formatted,
                        'discount_total' => $cart->discountTotal?->formatted,
                    ],
                ]);
            }

            return redirect()->route('storefront.cart.index')
                ->with('success', 'Discount applied successfully');
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove discount/coupon from the cart.
     */
    public function removeDiscount(Request $request)
    {
        try {
            $this->cartManager->removeDiscount();

            $cart = CartSession::current();
            $cart?->calculate();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Discount removed',
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'total' => $cart?->total?->formatted,
                    ],
                ]);
            }

            return redirect()->route('storefront.cart.index')
                ->with('success', 'Discount removed');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get cart summary (for AJAX requests).
     */
    public function summary(): JsonResponse
    {
        $cart = CartSession::current();
        
        if ($cart) {
            $cart->calculate();
        }

        return response()->json([
            'cart' => [
                'item_count' => $this->cartManager->getItemCount(),
                'has_items' => $this->cartManager->hasItems(),
                'total' => $cart?->total?->formatted,
                'subtotal' => $cart?->subTotal?->formatted,
                'tax_total' => $cart?->taxTotal?->formatted,
                'shipping_total' => $cart?->shippingTotal?->formatted,
                'discount_total' => $cart?->discountTotal?->formatted,
                'coupon_code' => $cart?->coupon_code,
            ],
        ]);
    }
}

