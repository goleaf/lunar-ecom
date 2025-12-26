<?php

namespace App\Http\Controllers\Frontend;

use App\Events\CartAddressChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Models\Order;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Display the checkout page.
     * 
     * See: https://docs.lunarphp.com/1.x/reference/carts
     */
    public function index()
    {
        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            return redirect()->route('frontend.cart.index')
                ->with('error', __('frontend.messages.cart_empty'));
        }

        // Check if cart is locked
        if ($this->checkoutService->isCartLocked($cart)) {
            return redirect()->route('frontend.cart.index')
                ->with('error', __('frontend.messages.cart_locked'));
        }

        // Start checkout lock
        $lock = $this->checkoutService->startCheckout($cart);

        // Calculate cart totals to show accurate pricing
        // See: https://docs.lunarphp.com/1.x/reference/carts#hydrating-the-cart-totals
        $cart->calculate();

        return view('frontend.checkout.index', compact('cart', 'lock'));
    }

    /**
     * Store the order (process checkout).
     */
    public function store(CheckoutRequest $request)
    {

        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            return redirect()->route('frontend.cart.index')
                ->with('error', __('frontend.messages.cart_empty'));
        }

        // Get active checkout lock
        $lock = $this->checkoutService->getActiveLock($cart);

        if (!$lock) {
            // Start checkout if no lock exists
            $lock = $this->checkoutService->startCheckout($cart);
        }

        // Set shipping and billing addresses on cart
        // See: https://docs.lunarphp.com/1.x/reference/carts#adding-shippingbilling-address
        $cart->setShippingAddress($request->shipping_address);
        $cart->setBillingAddress($request->billing_address);
        
        // Trigger address change event for repricing
        event(new CartAddressChanged($cart));

        // Force repricing before checkout (ensures prices are up-to-date)
        $cartManager = app(\App\Contracts\CartManagerInterface::class);
        $cartManager->forceReprice();

        // Store checkout snapshot if enabled
        if (config('lunar.cart.pricing.store_snapshots', false)) {
            $pricingEngine = app(\App\Services\CartPricingEngine::class);
            $pricingResult = $pricingEngine->calculateCartPrices($cart);
            \App\Models\CartPricingSnapshot::create([
                'cart_id' => $cart->id,
                'snapshot_type' => 'checkout',
                'pricing_data' => $pricingResult->toArray(),
                'trigger' => 'checkout',
                'pricing_version' => (string) ($cart->pricing_version ?? 0),
            ]);
        }

        // Recalculate cart with addresses for accurate tax calculation
        // See: https://docs.lunarphp.com/1.x/reference/carts#calculating-tax
        $cart->calculate();

        try {
            // Process checkout with state machine
            $paymentData = [
                'method' => $request->input('payment_method', 'card'),
                'token' => $request->input('payment_token'),
            ];

            $order = $this->checkoutService->processCheckout($lock, $paymentData);

            return redirect()->route('frontend.checkout.confirmation', $order)
                ->with('success', __('frontend.messages.order_placed'));

        } catch (\Exception $e) {
            // Release checkout lock on failure
            $this->checkoutService->releaseCheckout($lock);

            return redirect()->route('frontend.checkout.index')
                ->with('error', __('frontend.messages.checkout_failed', ['message' => $e->getMessage()]))
                ->withInput();
        }
    }

    /**
     * Display order confirmation.
     */
    public function confirmation(Order $order)
    {
        // Ensure user can view this order (owns the order)
        $this->authorize('view', $order);
        
        return view('frontend.checkout.confirmation', compact('order'));
    }
}



