<?php

namespace App\Services;

use App\Events\CheckoutCompleted;
use App\Events\CheckoutFailed;
use App\Events\CheckoutStarted;
use App\Exceptions\CheckoutException;
use App\Models\CheckoutLock;
use App\Models\PriceSnapshot;
use App\Models\StockReservation;
use App\Services\CheckoutLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Order;

/**
 * Checkout State Machine Service
 * 
 * Manages checkout phases with atomic operations and rollback support.
 */
class CheckoutStateMachine
{
    public const PHASE_CART_VALIDATION = 'cart_validation';
    public const PHASE_INVENTORY_RESERVATION = 'inventory_reservation';
    public const PHASE_PRICE_LOCK = 'price_lock';
    public const PHASE_PAYMENT_AUTHORIZATION = 'payment_authorization';
    public const PHASE_ORDER_CREATION = 'order_creation';
    public const PHASE_PAYMENT_CAPTURE = 'payment_capture';
    public const PHASE_STOCK_COMMIT = 'stock_commit';

    public function __construct(
        protected StockService $stockService,
        protected CheckoutLogger $logger
    ) {}

    /**
     * Start checkout process.
     */
    public function startCheckout(Cart $cart, ?int $ttlMinutes = null): CheckoutLock
    {
        $ttlMinutes = $ttlMinutes ?? config('checkout.default_ttl_minutes', 15);
        $maxTtl = config('checkout.max_ttl_minutes', 60);
        
        // Enforce maximum TTL
        if ($ttlMinutes > $maxTtl) {
            $ttlMinutes = $maxTtl;
        }

        return DB::transaction(function () use ($cart, $ttlMinutes) {
            // Check for existing active lock
            $existingLock = CheckoutLock::where('cart_id', $cart->id)
                ->where('session_id', session()->getId())
                ->active()
                ->first();

            if ($existingLock) {
                return $existingLock;
            }

            // Create new lock
            $lock = CheckoutLock::create([
                'cart_id' => $cart->id,
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'state' => CheckoutLock::STATE_PENDING,
                'locked_at' => now(),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);

            $this->logger->checkoutStarted($lock);
            event(new CheckoutStarted($lock));

            return $lock;
        });
    }

    /**
     * Execute checkout phases.
     */
    public function executeCheckout(CheckoutLock $lock, array $paymentData = []): Order
    {
        $rollbackStack = [];

        try {
            // Phase 1: Cart Validation
            $this->validateCart($lock);
            $rollbackStack[] = ['method' => 'rollbackCartValidation', 'lock' => $lock];

            // Phase 2: Inventory Reservation
            $reservations = $this->reserveInventory($lock);
            $rollbackStack[] = ['method' => 'rollbackInventoryReservation', 'lock' => $lock, 'reservations' => $reservations];

            // Phase 3: Price Lock
            $this->lockPrices($lock);
            $rollbackStack[] = ['method' => 'rollbackPriceLock', 'lock' => $lock];

            // Phase 4: Payment Authorization
            $paymentAuth = $this->authorizePayment($lock, $paymentData);
            $rollbackStack[] = ['method' => 'rollbackPaymentAuthorization', 'lock' => $lock, 'payment' => $paymentAuth];

            // Phase 5: Order Creation
            $order = $this->createOrder($lock);
            $rollbackStack[] = ['method' => 'rollbackOrderCreation', 'lock' => $lock, 'order' => $order];

            // Phase 6: Payment Capture
            $this->capturePayment($lock, $order, $paymentAuth);
            $rollbackStack[] = ['method' => 'rollbackPaymentCapture', 'lock' => $lock, 'order' => $order];

            // Phase 7: Stock Commit
            $this->commitStock($lock, $order, $reservations);

            // Mark checkout as completed
            $lock->markCompleted();

            $this->logger->checkoutCompleted($lock, $order);
            event(new CheckoutCompleted($lock, $order));

            return $order;

        } catch (\Exception $e) {
            // Rollback in reverse order
            $this->rollback($rollbackStack, $lock, $e);

            throw $e;
        }
    }

    /**
     * Phase 1: Validate cart.
     */
    protected function validateCart(CheckoutLock $lock): void
    {
        $lock->updateState(CheckoutLock::STATE_VALIDATING, self::PHASE_CART_VALIDATION);

        $cart = $lock->cart;

        // Check cart exists and has items
        if (!$cart || $cart->lines->isEmpty()) {
            throw new CheckoutException(
                'Cart is empty or invalid',
                self::PHASE_CART_VALIDATION,
                ['cart_id' => $cart?->id]
            );
        }

        // Validate cart can create order
        if (!$cart->canCreateOrder()) {
            throw new CheckoutException(
                'Cart is not ready to create an order',
                self::PHASE_CART_VALIDATION,
                ['cart_id' => $cart->id]
            );
        }

        // Validate addresses
        if (!$cart->shippingAddress || !$cart->billingAddress) {
            throw new CheckoutException(
                'Shipping and billing addresses are required',
                self::PHASE_CART_VALIDATION,
                [
                    'cart_id' => $cart->id,
                    'has_shipping' => (bool) $cart->shippingAddress,
                    'has_billing' => (bool) $cart->billingAddress,
                ]
            );
        }

        // Validate stock availability (without reserving)
        foreach ($cart->lines as $line) {
            if ($line->purchasable instanceof \Lunar\Models\ProductVariant) {
                $variant = $line->purchasable;
                $available = $this->stockService->getTotalAvailableStock($variant);
                
                if ($variant->purchasable === 'in_stock' && $available < $line->quantity) {
                    throw new CheckoutException(
                        "Insufficient stock for {$variant->sku}. Only {$available} available.",
                        self::PHASE_CART_VALIDATION,
                        [
                            'variant_id' => $variant->id,
                            'variant_sku' => $variant->sku,
                            'requested' => $line->quantity,
                            'available' => $available,
                        ]
                    );
                }
            }
        }

        $this->logger->phaseTransition($lock, self::PHASE_CART_VALIDATION);
    }

    /**
     * Phase 2: Reserve inventory.
     */
    protected function reserveInventory(CheckoutLock $lock): array
    {
        $lock->updateState(CheckoutLock::STATE_RESERVING, self::PHASE_INVENTORY_RESERVATION);

        $cart = $lock->cart;
        $reservations = [];

        foreach ($cart->lines as $line) {
            if ($line->purchasable instanceof \Lunar\Models\ProductVariant) {
                $variant = $line->purchasable;
                
                // Atomic reservation per variant
                $reservation = DB::transaction(function () use ($variant, $line, $lock) {
                    $reservation = $this->stockService->reserveStock(
                        variant: $variant,
                        quantity: $line->quantity,
                        sessionId: $lock->session_id,
                        userId: $lock->user_id,
                        expiryMinutes: $lock->expires_at->diffInMinutes(now())
                    );

                    if (!$reservation) {
                        throw new CheckoutException(
                            "Failed to reserve stock for variant {$variant->sku}",
                            self::PHASE_INVENTORY_RESERVATION,
                            [
                                'variant_id' => $variant->id,
                                'variant_sku' => $variant->sku,
                                'quantity' => $line->quantity,
                            ]
                        );
                    }

                    // Link reservation to checkout lock
                    $reservation->update([
                        'reference_type' => CheckoutLock::class,
                        'reference_id' => $lock->id,
                    ]);

                    return $reservation;
                });

                $reservations[] = $reservation;
            }
        }

        $this->logger->phaseTransition($lock, self::PHASE_INVENTORY_RESERVATION, [
            'reservations_count' => count($reservations),
        ]);
        $this->logger->stockReserved($lock, count($reservations));

        return $reservations;
    }

    /**
     * Phase 3: Lock prices.
     */
    protected function lockPrices(CheckoutLock $lock): void
    {
        $lock->updateState(CheckoutLock::STATE_LOCKING_PRICES, self::PHASE_PRICE_LOCK);

        $cart = $lock->cart->fresh();
        $cart->calculate(); // Ensure prices are calculated

        // Validate cart has calculated totals
        if (!$cart->subTotal || !$cart->total) {
            throw new CheckoutException(
                'Cart totals are not calculated. Cannot lock prices.',
                self::PHASE_PRICE_LOCK,
                ['cart_id' => $cart->id]
            );
        }

        // Create cart-level snapshot
        PriceSnapshot::create([
            'checkout_lock_id' => $lock->id,
            'cart_id' => $cart->id,
            'unit_price' => 0,
            'sub_total' => $cart->subTotal->value ?? 0,
            'discount_total' => $cart->discountTotal->value ?? 0,
            'tax_total' => $cart->taxTotal->value ?? 0,
            'total' => $cart->total->value ?? 0,
            'discount_breakdown' => $cart->discountBreakdown ?? null,
            'applied_discounts' => $this->extractAppliedDiscounts($cart),
            'tax_breakdown' => $cart->taxBreakdown ?? null,
            'currency_code' => $cart->currency->code,
            'compare_currency_code' => $cart->currency->code,
            'exchange_rate' => $cart->currency->exchange_rate ?? 1,
            'coupon_code' => $cart->coupon_code,
            'promotion_details' => $this->extractPromotionDetails($cart),
            'snapshot_at' => now(),
        ]);

        // Create line-level snapshots
        foreach ($cart->lines as $line) {
            // Ensure line has calculated prices
            if (!$line->unitPrice || !$line->total) {
                Log::warning('Cart line missing calculated prices', [
                    'lock_id' => $lock->id,
                    'cart_line_id' => $line->id,
                ]);
            }

            PriceSnapshot::create([
                'checkout_lock_id' => $lock->id,
                'cart_id' => $cart->id,
                'cart_line_id' => $line->id,
                'unit_price' => $line->unitPrice->value ?? 0,
                'sub_total' => $line->subTotal->value ?? 0,
                'discount_total' => $line->discountTotal->value ?? 0,
                'tax_total' => $line->taxTotal->value ?? 0,
                'total' => $line->total->value ?? 0,
                'discount_breakdown' => $line->discount_breakdown ?? null,
                'applied_discounts' => $line->applied_rules ?? null,
                'tax_breakdown' => $line->tax_breakdown ?? null,
                'currency_code' => $cart->currency->code,
                'snapshot_at' => now(),
            ]);
        }

        $this->logger->phaseTransition($lock, self::PHASE_PRICE_LOCK);
        $this->logger->priceLockCreated($lock, $snapshot->total, $snapshot->currency_code);
    }

    /**
     * Phase 4: Authorize payment.
     */
    protected function authorizePayment(CheckoutLock $lock, array $paymentData): array
    {
        $lock->updateState(CheckoutLock::STATE_AUTHORIZING, self::PHASE_PAYMENT_AUTHORIZATION);

        // TODO: Integrate with payment gateway
        // For now, simulate authorization
        $cart = $lock->cart->fresh();
        $cart->calculate();
        
        $paymentAuth = [
            'authorization_id' => 'auth_' . uniqid(),
            'status' => 'authorized',
            'amount' => $cart->total->value ?? 0,
            'currency' => $cart->currency->code,
            'authorized_at' => now()->toIso8601String(),
        ];

        // Store in lock metadata
        $metadata = $lock->metadata ?? [];
        $metadata['payment_authorization'] = $paymentAuth;
        $lock->update(['metadata' => $metadata]);

        $this->logger->phaseTransition($lock, self::PHASE_PAYMENT_AUTHORIZATION, [
            'authorization_id' => $paymentAuth['authorization_id'],
        ]);

        return $paymentAuth;
    }

    /**
     * Phase 5: Create order.
     */
    protected function createOrder(CheckoutLock $lock): Order
    {
        $lock->updateState(CheckoutLock::STATE_CREATING_ORDER, self::PHASE_ORDER_CREATION);

        $cart = $lock->cart;
        $snapshot = PriceSnapshot::where('checkout_lock_id', $lock->id)
            ->whereNull('cart_line_id')
            ->first();

        // Create order from cart
        $order = CartSession::createOrder();

        // Apply price snapshots to order
        if ($snapshot) {
            $order->update([
                'sub_total' => $snapshot->sub_total,
                'discount_total' => $snapshot->discount_total,
                'tax_total' => $snapshot->tax_total,
                'total' => $snapshot->total,
                'tax_breakdown' => $snapshot->tax_breakdown,
                'currency_code' => $snapshot->currency_code,
                'compare_currency_code' => $snapshot->compare_currency_code,
                'exchange_rate' => $snapshot->exchange_rate,
            ]);

            // Update order lines with snapshots
            // Match by purchasable since cart lines become order lines
            foreach ($order->lines as $orderLine) {
                // Find corresponding cart line snapshot by purchasable
                $cartLine = $cart->lines()
                    ->where('purchasable_type', $orderLine->purchasable_type)
                    ->where('purchasable_id', $orderLine->purchasable_id)
                    ->first();

                if ($cartLine) {
                    $lineSnapshot = PriceSnapshot::where('checkout_lock_id', $lock->id)
                        ->where('cart_line_id', $cartLine->id)
                        ->first();

                    if ($lineSnapshot) {
                        $orderLine->update([
                            'unit_price' => $lineSnapshot->unit_price,
                            'sub_total' => $lineSnapshot->sub_total,
                            'discount_total' => $lineSnapshot->discount_total,
                            'tax_total' => $lineSnapshot->tax_total,
                            'total' => $lineSnapshot->total,
                            'tax_breakdown' => $lineSnapshot->tax_breakdown,
                        ]);
                    }
                }
            }
        }

        // Link order to lock
        $metadata = $lock->metadata ?? [];
        $metadata['order_id'] = $order->id;
        $lock->update(['metadata' => $metadata]);

        $this->logger->phaseTransition($lock, self::PHASE_ORDER_CREATION, [
            'order_id' => $order->id,
            'order_reference' => $order->reference,
        ]);

        return $order;
    }

    /**
     * Phase 6: Capture payment.
     */
    protected function capturePayment(CheckoutLock $lock, Order $order, array $paymentAuth): void
    {
        $lock->updateState(CheckoutLock::STATE_CAPTURING, self::PHASE_PAYMENT_CAPTURE);

        // TODO: Integrate with payment gateway to capture payment
        // For now, simulate capture
        $capture = [
            'capture_id' => 'capture_' . uniqid(),
            'authorization_id' => $paymentAuth['authorization_id'],
            'status' => 'captured',
            'amount' => $order->total,
            'captured_at' => now()->toIso8601String(),
        ];

        // Store in order metadata
        $meta = $order->meta ?? [];
        $meta['payment'] = $capture;
        $order->update(['meta' => $meta]);

        $this->logger->phaseTransition($lock, self::PHASE_PAYMENT_CAPTURE, [
            'order_id' => $order->id,
            'capture_id' => $capture['capture_id'],
        ]);
    }

    /**
     * Phase 7: Commit stock.
     */
    protected function commitStock(CheckoutLock $lock, Order $order, array $reservations): void
    {
        $lock->updateState(CheckoutLock::STATE_COMMITTING, self::PHASE_STOCK_COMMIT);

        foreach ($reservations as $reservation) {
            $this->stockService->confirmReservation($reservation, $order);
        }

        $this->logger->phaseTransition($lock, self::PHASE_STOCK_COMMIT, [
            'order_id' => $order->id,
            'reservations_count' => count($reservations),
        ]);
    }

    /**
     * Rollback checkout operations.
     */
    protected function rollback(array $rollbackStack, CheckoutLock $lock, \Exception $exception): void
    {
        $this->logger->rollbackStarted($lock, $lock->phase ?? 'unknown', $rollbackStack);
        $this->logger->checkoutFailed($lock, $exception);

        // Execute rollbacks in reverse order
        foreach (array_reverse($rollbackStack) as $rollback) {
            try {
                $this->{$rollback['method']}($rollback);
                $this->logger->rollbackStep($lock, $rollback['method'], true);
            } catch (\Exception $e) {
                $this->logger->rollbackStep($lock, $rollback['method'], false, $e->getMessage());
            }
        }

        // Mark lock as failed
        $lock->markFailed(
            $lock->phase ?? 'unknown',
            [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'rolled_back_at' => now()->toIso8601String(),
            ]
        );

        // Fire failed event
        event(new CheckoutFailed($lock, $exception));
    }

    /**
     * Rollback cart validation.
     */
    protected function rollbackCartValidation(array $data): void
    {
        // Nothing to rollback for validation
    }

    /**
     * Rollback inventory reservation.
     */
    protected function rollbackInventoryReservation(array $data): void
    {
        $reservations = $data['reservations'] ?? [];
        
        foreach ($reservations as $reservation) {
            $this->stockService->releaseReservation($reservation);
        }

        $this->logger->stockReleased($data['lock'], count($reservations));
    }

    /**
     * Rollback price lock.
     */
    protected function rollbackPriceLock(array $data): void
    {
        // Price snapshots are kept for audit, but cart is unlocked
        Log::info('Price lock released', ['lock_id' => $data['lock']->id]);
    }

    /**
     * Rollback payment authorization.
     */
    protected function rollbackPaymentAuthorization(array $data): void
    {
        $payment = $data['payment'] ?? null;
        
        if ($payment) {
            // TODO: Void/cancel payment authorization with gateway
            Log::info('Payment authorization voided', [
                'lock_id' => $data['lock']->id,
                'authorization_id' => $payment['authorization_id'] ?? null,
            ]);
        }
    }

    /**
     * Rollback order creation.
     */
    protected function rollbackOrderCreation(array $data): void
    {
        $order = $data['order'] ?? null;
        
        if ($order) {
            // Cancel order (don't delete for audit trail)
            $order->update(['status' => 'cancelled']);
            Log::info('Order cancelled', [
                'lock_id' => $data['lock']->id,
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Rollback payment capture.
     */
    protected function rollbackPaymentCapture(array $data): void
    {
        $order = $data['order'] ?? null;
        
        if ($order && isset($order->meta['payment']['capture_id'])) {
            // TODO: Refund captured payment with gateway
            Log::info('Payment capture refunded', [
                'lock_id' => $data['lock']->id,
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Extract applied discounts from cart.
     */
    protected function extractAppliedDiscounts(Cart $cart): ?array
    {
        // Extract discount information from cart
        return $cart->discountBreakdown ?? null;
    }

    /**
     * Extract promotion details from cart.
     */
    protected function extractPromotionDetails(Cart $cart): ?array
    {
        return [
            'coupon_code' => $cart->coupon_code,
            'applied_at' => now()->toIso8601String(),
        ];
    }
}

