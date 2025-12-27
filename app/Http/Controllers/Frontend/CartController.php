<?php

namespace App\Http\Controllers\Frontend;

use App\Contracts\CartManagerInterface;
use App\Http\Controllers\Controller;
use App\Services\CartPricing\CartPricingOutputFormatter;
use App\Traits\ChecksCheckoutLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

class CartController extends Controller
{
    use ChecksCheckoutLock;

    public function __construct(
        protected CartManagerInterface $cartManager,
        protected CartPricingOutputFormatter $pricingFormatter
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

        // Get complete cart breakdown with transparency fields
        $transparencyService = app(\App\Services\CartTransparencyService::class);
        $cartBreakdown = $transparencyService->getCartBreakdown($cart);

        return view('frontend.cart.index', compact('cart', 'cartBreakdown'));
    }

    /**
     * Add an item to the cart.
     * 
     * See: https://docs.lunarphp.com/1.x/reference/carts#add-a-cart-line
     */
    public function add(Request $request)
    {
        // Prevent modifications during checkout
        $this->ensureCartNotLocked();

        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
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
                
                $transparencyService = app(\App\Services\CartTransparencyService::class);
                $breakdown = $transparencyService->getCartBreakdown($cart);
                
                return response()->json([
                    'success' => true,
                    'message' => __('frontend.messages.item_added_to_cart'),
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'grand_total' => $breakdown['grand_total'],
                        'subtotal_pre_discount' => $breakdown['subtotal_pre_discount'],
                        'total_discounts' => $breakdown['total_discounts'],
                        'tax_total' => $breakdown['tax_total'],
                        'shipping_total' => $breakdown['shipping_total'],
                        // Legacy
                        'total' => $breakdown['grand_total']['formatted'],
                    ],
                ]);
            }

            return redirect()->route('frontend.cart.index')
                ->with('success', __('frontend.messages.item_added_to_cart'));
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
        // Prevent modifications during checkout
        $this->ensureCartNotLocked();

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
                
                $transparencyService = app(\App\Services\CartTransparencyService::class);
                $breakdown = $transparencyService->getCartBreakdown($cart);
                
                return response()->json([
                    'success' => true,
                    'message' => __('frontend.messages.cart_updated'),
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'grand_total' => $breakdown['grand_total'],
                        'subtotal_pre_discount' => $breakdown['subtotal_pre_discount'],
                        'total_discounts' => $breakdown['total_discounts'],
                        'tax_total' => $breakdown['tax_total'],
                        'shipping_total' => $breakdown['shipping_total'],
                        // Legacy
                        'total' => $breakdown['grand_total']['formatted'],
                    ],
                ]);
            }

            return redirect()->route('frontend.cart.index')
                ->with('success', __('frontend.messages.cart_updated'));
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
        // Prevent modifications during checkout
        $this->ensureCartNotLocked();

        try {
            CartSession::remove($lineId);

            if ($request->expectsJson()) {
                $cart = CartSession::current();
                $cart?->calculate();
                
                $transparencyService = app(\App\Services\CartTransparencyService::class);
                $breakdown = $transparencyService->getCartBreakdown($cart);
                
                return response()->json([
                    'success' => true,
                    'message' => __('frontend.messages.item_removed_from_cart'),
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'grand_total' => $breakdown['grand_total'],
                        'subtotal_pre_discount' => $breakdown['subtotal_pre_discount'],
                        'total_discounts' => $breakdown['total_discounts'],
                        'tax_total' => $breakdown['tax_total'],
                        'shipping_total' => $breakdown['shipping_total'],
                        // Legacy
                        'total' => $breakdown['grand_total']['formatted'],
                    ],
                ]);
            }

            return redirect()->route('frontend.cart.index')
                ->with('success', __('frontend.messages.item_removed_from_cart'));
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
        // Prevent modifications during checkout
        $this->ensureCartNotLocked();

        CartSession::clear();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('frontend.messages.cart_cleared'),
                'cart' => [
                    'item_count' => 0,
                    'total' => null,
                ],
            ]);
        }

        return redirect()->route('frontend.cart.index')
            ->with('success', __('frontend.messages.cart_cleared'));
    }

    /**
     * Apply a discount/coupon code to the cart.
     */
    public function applyDiscount(Request $request)
    {
        // Prevent modifications during checkout
        $this->ensureCartNotLocked();

        $request->validate([
            'coupon_code' => 'required|string|max:255',
        ]);

        try {
            $this->cartManager->applyDiscount($request->coupon_code);

            $cart = CartSession::current();
            $cart?->calculate();

            if ($request->expectsJson()) {
                $transparencyService = app(\App\Services\CartTransparencyService::class);
                $breakdown = $transparencyService->getCartBreakdown($cart);
                
                return response()->json([
                    'success' => true,
                    'message' => __('frontend.messages.discount_applied'),
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'grand_total' => $breakdown['grand_total'],
                        'subtotal_pre_discount' => $breakdown['subtotal_pre_discount'],
                        'total_discounts' => $breakdown['total_discounts'],
                        'discount_breakdown' => $breakdown['discount_breakdown'],
                        'applied_rules' => $breakdown['applied_rules'],
                        'tax_total' => $breakdown['tax_total'],
                        'shipping_total' => $breakdown['shipping_total'],
                        // Legacy
                        'total' => $breakdown['grand_total']['formatted'],
                        'discount_total' => $breakdown['total_discounts']['formatted'],
                    ],
                ]);
            }

            return redirect()->route('frontend.cart.index')
                ->with('success', __('frontend.messages.discount_applied'));
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
        // Prevent modifications during checkout
        $this->ensureCartNotLocked();

        try {
            $this->cartManager->removeDiscount();

            $cart = CartSession::current();
            $cart?->calculate();

            if ($request->expectsJson()) {
                $transparencyService = app(\App\Services\CartTransparencyService::class);
                $breakdown = $transparencyService->getCartBreakdown($cart);
                
                return response()->json([
                    'success' => true,
                    'message' => __('frontend.messages.discount_removed'),
                    'cart' => [
                        'item_count' => $this->cartManager->getItemCount(),
                        'grand_total' => $breakdown['grand_total'],
                        'subtotal_pre_discount' => $breakdown['subtotal_pre_discount'],
                        'total_discounts' => $breakdown['total_discounts'],
                        'applied_rules' => $breakdown['applied_rules'],
                        'tax_total' => $breakdown['tax_total'],
                        'shipping_total' => $breakdown['shipping_total'],
                        // Legacy
                        'total' => $breakdown['grand_total']['formatted'],
                    ],
                ]);
            }

            return redirect()->route('frontend.cart.index')
                ->with('success', __('frontend.messages.discount_removed'));
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
     * 
     * Always exposes all transparency fields:
     * - Subtotal (pre-discount)
     * - Total discounts
     * - Tax breakdown
     * - Shipping cost
     * - Grand total
     * - Audit trail of applied rules
     */
    public function summary(): JsonResponse
    {
        $cart = CartSession::current();
        
        if ($cart) {
            $cart->calculate();
        }

        $transparencyService = app(\App\Services\CartTransparencyService::class);
        $breakdown = $transparencyService->getCartBreakdown($cart);

        return response()->json([
            'cart' => [
                'item_count' => $this->cartManager->getItemCount(),
                'has_items' => $this->cartManager->hasItems(),
                
                // Core totals (always exposed)
                'subtotal_pre_discount' => $breakdown['subtotal_pre_discount'],
                'subtotal_discounted' => $breakdown['subtotal_discounted'],
                'total_discounts' => $breakdown['total_discounts'],
                'shipping_total' => $breakdown['shipping_total'],
                'tax_total' => $breakdown['tax_total'],
                'grand_total' => $breakdown['grand_total'],
                
                // Breakdowns (always exposed)
                'discount_breakdown' => $breakdown['discount_breakdown'],
                'tax_breakdown' => $breakdown['tax_breakdown'],
                'shipping_breakdown' => $breakdown['shipping_breakdown'],
                
                // Audit trail (always exposed)
                'applied_rules' => $breakdown['applied_rules'],
                
                // Legacy fields for backward compatibility
                'total' => $breakdown['grand_total']['formatted'],
                'subtotal' => $breakdown['subtotal_pre_discount']['formatted'],
                'tax_total_formatted' => $breakdown['tax_total']['formatted'],
                'shipping_total_formatted' => $breakdown['shipping_total']['formatted'],
                'discount_total_formatted' => $breakdown['total_discounts']['formatted'],
                'coupon_code' => $cart?->coupon_code,
                
                // Metadata
                'currency' => $breakdown['currency'],
                'currency_symbol' => $breakdown['currency_symbol'],
            ],
        ]);
    }

    /**
     * Get detailed pricing information with audit trail.
     * 
     * Returns complete pricing breakdown including:
     * - Subtotal (pre-discount)
     * - Total discounts
     * - Tax breakdown
     * - Shipping cost
     * - Grand total
     * - Audit trail of applied rules
     */
    public function pricing(): JsonResponse
    {
        $cart = CartSession::current();
        
        if (!$cart) {
            return response()->json([
                'error' => 'No active cart found',
            ], 404);
        }

        // Force repricing before returning detailed pricing
        $this->cartManager->forceReprice();
        
        // Get detailed pricing information
        $pricing = $this->pricingFormatter->formatCartPricing($cart);

        return response()->json([
            'pricing' => $pricing,
        ]);
    }
}



