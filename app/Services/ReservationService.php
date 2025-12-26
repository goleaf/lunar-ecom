<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\InventoryLevel;
use App\Models\Warehouse;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Reservation Service.
 * 
 * Handles:
 * - Cart-based reservations
 * - Reservation expiration
 * - Partial reservations
 * - Order-confirmed reservations
 * - Manual reservation override
 * - Race-condition safe locking
 */
class ReservationService
{
    /**
     * Default reservation expiration time (minutes).
     */
    protected int $defaultExpirationMinutes = 30;

    /**
     * Default lock expiration time (seconds).
     */
    protected int $defaultLockExpirationSeconds = 30;

    /**
     * Create cart-based reservation.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Cart  $cart
     * @param  int|null  $warehouseId
     * @param  int|null  $expirationMinutes
     * @return StockReservation
     */
    public function createCartReservation(
        ProductVariant $variant,
        int $quantity,
        Cart $cart,
        ?int $warehouseId = null,
        ?int $expirationMinutes = null
    ): StockReservation {
        return DB::transaction(function () use ($variant, $quantity, $cart, $warehouseId, $expirationMinutes) {
            // Acquire lock
            $lockToken = $this->acquireLock($variant, $warehouseId);

            try {
                // Get or select warehouse
                if (!$warehouseId) {
                    $warehouse = $this->selectFulfillmentWarehouse($variant, $quantity);
                    $warehouseId = $warehouse?->id;
                }

                if (!$warehouseId) {
                    throw new \RuntimeException('No warehouse available for reservation.');
                }

                // Get inventory level
                $inventoryLevel = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Calculate available stock
                $available = $inventoryLevel->available_quantity;

                // Reserve what's available (partial reservation if needed)
                $reservedQuantity = min($quantity, $available);

                if ($reservedQuantity <= 0) {
                    throw new \RuntimeException('No stock available for reservation.');
                }

                // Create reservation
                $reservation = StockReservation::create([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                    'inventory_level_id' => $inventoryLevel->id,
                    'quantity' => $quantity,
                    'reserved_quantity' => $reservedQuantity,
                    'status' => 'cart',
                    'reference_type' => Cart::class,
                    'reference_id' => $cart->id,
                    'session_id' => $cart->meta['session_id'] ?? session()->getId(),
                    'user_id' => $cart->user_id,
                    'lock_token' => $lockToken,
                    'locked_at' => now(),
                    'lock_expires_at' => now()->addSeconds($this->defaultLockExpirationSeconds),
                    'expires_at' => now()->addMinutes($expirationMinutes ?? $this->defaultExpirationMinutes),
                    'metadata' => [
                        'cart_id' => $cart->id,
                        'cart_line_id' => null, // Can be set when linking to cart line
                    ],
                ]);

                // Update inventory level
                $inventoryLevel->increment('reserved_quantity', $reservedQuantity);
                $inventoryLevel->updateStatus();
                $inventoryLevel->save();

                return $reservation->fresh();
            } finally {
                // Release lock
                $this->releaseLock($lockToken);
            }
        });
    }

    /**
     * Confirm reservation (order-confirmed).
     *
     * @param  StockReservation  $reservation
     * @param  Order  $order
     * @param  int|null  $userId
     * @return StockReservation
     */
    public function confirmReservation(
        StockReservation $reservation,
        Order $order,
        ?int $userId = null
    ): StockReservation {
        return DB::transaction(function () use ($reservation, $order, $userId) {
            // Acquire lock
            $lockToken = $this->acquireLock($reservation->productVariant, $reservation->warehouse_id);

            try {
                // Update reservation
                $reservation->update([
                    'status' => 'order_confirmed',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'confirmed_at' => now(),
                    'confirmed_by' => $userId ?? auth()->id(),
                    'expires_at' => null, // Order-confirmed reservations don't expire
                    'lock_token' => null,
                    'locked_at' => null,
                    'lock_expires_at' => null,
                    'metadata' => array_merge($reservation->metadata ?? [], [
                        'order_id' => $order->id,
                        'confirmed_at' => now()->toIso8601String(),
                    ]),
                ]);

                return $reservation->fresh();
            } finally {
                $this->releaseLock($lockToken);
            }
        });
    }

    /**
     * Create manual reservation override.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  string  $reason
     * @param  int|null  $userId
     * @param  array  $metadata
     * @return StockReservation
     */
    public function createManualReservation(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        string $reason,
        ?int $userId = null,
        array $metadata = []
    ): StockReservation {
        return DB::transaction(function () use ($variant, $quantity, $warehouseId, $reason, $userId, $metadata) {
            // Acquire lock
            $lockToken = $this->acquireLock($variant, $warehouseId);

            try {
                // Get inventory level
                $inventoryLevel = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Manual reservations can exceed available stock
                $available = $inventoryLevel->available_quantity;
                $reservedQuantity = min($quantity, $available);

                // Create reservation
                $reservation = StockReservation::create([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                    'inventory_level_id' => $inventoryLevel->id,
                    'quantity' => $quantity,
                    'reserved_quantity' => $reservedQuantity,
                    'status' => 'manual',
                    'reference_type' => null,
                    'reference_id' => null,
                    'user_id' => $userId ?? auth()->id(),
                    'lock_token' => $lockToken,
                    'locked_at' => now(),
                    'lock_expires_at' => now()->addSeconds($this->defaultLockExpirationSeconds),
                    'expires_at' => null, // Manual reservations don't expire by default
                    'override_reason' => $reason,
                    'metadata' => array_merge($metadata, [
                        'manual' => true,
                        'created_by' => $userId ?? auth()->id(),
                    ]),
                ]);

                // Update inventory level
                if ($reservedQuantity > 0) {
                    $inventoryLevel->increment('reserved_quantity', $reservedQuantity);
                    $inventoryLevel->updateStatus();
                    $inventoryLevel->save();
                }

                return $reservation->fresh();
            } finally {
                $this->releaseLock($lockToken);
            }
        });
    }

    /**
     * Complete partial reservation (fulfill remaining quantity).
     *
     * @param  StockReservation  $reservation
     * @param  int  $additionalQuantity
     * @return StockReservation
     */
    public function completePartialReservation(
        StockReservation $reservation,
        int $additionalQuantity
    ): StockReservation {
        return DB::transaction(function () use ($reservation, $additionalQuantity) {
            // Acquire lock
            $lockToken = $this->acquireLock($reservation->productVariant, $reservation->warehouse_id);

            try {
                // Check if reservation is partial
                if ($reservation->isFullyReserved()) {
                    throw new \RuntimeException('Reservation is already fully reserved.');
                }

                $remaining = $reservation->remaining_quantity;
                $toReserve = min($additionalQuantity, $remaining);

                if ($toReserve <= 0) {
                    return $reservation;
                }

                // Get inventory level
                $inventoryLevel = InventoryLevel::where('product_variant_id', $reservation->product_variant_id)
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Check available stock
                $available = $inventoryLevel->available_quantity;
                $actualReserve = min($toReserve, $available);

                if ($actualReserve <= 0) {
                    throw new \RuntimeException('No additional stock available.');
                }

                // Update reservation
                $reservation->increment('reserved_quantity', $actualReserve);
                $reservation->update([
                    'lock_token' => $lockToken,
                    'locked_at' => now(),
                    'lock_expires_at' => now()->addSeconds($this->defaultLockExpirationSeconds),
                ]);

                // Update inventory level
                $inventoryLevel->increment('reserved_quantity', $actualReserve);
                $inventoryLevel->updateStatus();
                $inventoryLevel->save();

                return $reservation->fresh();
            } finally {
                $this->releaseLock($lockToken);
            }
        });
    }

    /**
     * Release reservation.
     *
     * @param  StockReservation  $reservation
     * @param  string|null  $reason
     * @return bool
     */
    public function releaseReservation(StockReservation $reservation, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($reservation, $reason) {
            // Acquire lock
            $lockToken = $this->acquireLock($reservation->productVariant, $reservation->warehouse_id);

            try {
                if ($reservation->is_released) {
                    return false; // Already released
                }

                // Get inventory level
                $inventoryLevel = InventoryLevel::where('product_variant_id', $reservation->product_variant_id)
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventoryLevel && $reservation->reserved_quantity > 0) {
                    // Release reserved quantity
                    $inventoryLevel->decrement('reserved_quantity', $reservation->reserved_quantity);
                    $inventoryLevel->updateStatus();
                    $inventoryLevel->save();
                }

                // Update reservation
                $reservation->update([
                    'is_released' => true,
                    'released_at' => now(),
                    'status' => 'released',
                    'lock_token' => null,
                    'locked_at' => null,
                    'lock_expires_at' => null,
                    'metadata' => array_merge($reservation->metadata ?? [], [
                        'released_at' => now()->toIso8601String(),
                        'release_reason' => $reason,
                    ]),
                ]);

                return true;
            } finally {
                $this->releaseLock($lockToken);
            }
        });
    }

    /**
     * Release expired reservations.
     *
     * @param  int|null  $limit
     * @return int Number of reservations released
     */
    public function releaseExpiredReservations(?int $limit = null): int
    {
        $expired = StockReservation::expired()
            ->where('status', 'cart') // Only release cart-based reservations
            ->limit($limit ?? 100)
            ->get();

        $released = 0;

        foreach ($expired as $reservation) {
            if ($this->releaseReservation($reservation, 'expired')) {
                $released++;
            }
        }

        return $released;
    }

    /**
     * Release expired locks.
     *
     * @return int Number of locks released
     */
    public function releaseExpiredLocks(): int
    {
        return StockReservation::whereNotNull('lock_token')
            ->where('lock_expires_at', '<=', now())
            ->update([
                'lock_token' => null,
                'locked_at' => null,
                'lock_expires_at' => null,
            ]);
    }

    /**
     * Acquire lock for race-condition safety.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return string Lock token
     */
    protected function acquireLock(ProductVariant $variant, ?int $warehouseId = null): string
    {
        $lockToken = Str::random(64);
        $lockKey = "reservation_lock:{$variant->id}:" . ($warehouseId ?? 'all');

        // Use cache lock for distributed locking
        $lock = cache()->lock($lockKey, $this->defaultLockExpirationSeconds);
        
        if (!$lock->get()) {
            throw new \RuntimeException('Could not acquire reservation lock. Please try again.');
        }

        // Store lock reference for later release
        cache()->put("reservation_lock_token:{$lockToken}", $lockKey, $this->defaultLockExpirationSeconds);

        return $lockToken;
    }

    /**
     * Release lock.
     *
     * @param  string  $lockToken
     * @return void
     */
    protected function releaseLock(string $lockToken): void
    {
        $lockKey = cache()->get("reservation_lock_token:{$lockToken}");
        
        if ($lockKey) {
            $lock = cache()->lock($lockKey);
            $lock->release();
            cache()->forget("reservation_lock_token:{$lockToken}");
        }
    }

    /**
     * Select fulfillment warehouse for reservation.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @return Warehouse|null
     */
    protected function selectFulfillmentWarehouse(ProductVariant $variant, int $quantity): ?Warehouse
    {
        $service = app(MultiWarehouseService::class);
        $warehouses = $service->getFulfillmentWarehouses($variant, $quantity);

        return $warehouses->first();
    }

    /**
     * Get reservations for cart.
     *
     * @param  Cart  $cart
     * @return \Illuminate\Support\Collection
     */
    public function getCartReservations(Cart $cart): \Illuminate\Support\Collection
    {
        return StockReservation::where('reference_type', Cart::class)
            ->where('reference_id', $cart->id)
            ->where('status', 'cart')
            ->active()
            ->get();
    }

    /**
     * Get reservations for order.
     *
     * @param  Order  $order
     * @return \Illuminate\Support\Collection
     */
    public function getOrderReservations(Order $order): \Illuminate\Support\Collection
    {
        return StockReservation::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('status', 'order_confirmed')
            ->get();
    }

    /**
     * Release all cart reservations.
     *
     * @param  Cart  $cart
     * @return int Number of reservations released
     */
    public function releaseCartReservations(Cart $cart): int
    {
        $reservations = $this->getCartReservations($cart);
        $released = 0;

        foreach ($reservations as $reservation) {
            if ($this->releaseReservation($reservation, 'cart_abandoned')) {
                $released++;
            }
        }

        return $released;
    }

    /**
     * Extend reservation expiration.
     *
     * @param  StockReservation  $reservation
     * @param  int  $additionalMinutes
     * @return StockReservation
     */
    public function extendReservation(StockReservation $reservation, int $additionalMinutes): StockReservation
    {
        $reservation->update([
            'expires_at' => $reservation->expires_at->addMinutes($additionalMinutes),
        ]);

        return $reservation->fresh();
    }

    /**
     * Get reservation summary.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return array
     */
    public function getReservationSummary(ProductVariant $variant, ?int $warehouseId = null): array
    {
        $query = StockReservation::where('product_variant_id', $variant->id)
            ->where('is_released', false);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $reservations = $query->get();

        return [
            'total_reserved' => $reservations->sum('reserved_quantity'),
            'cart_reservations' => $reservations->where('status', 'cart')->sum('reserved_quantity'),
            'order_confirmed_reservations' => $reservations->where('status', 'order_confirmed')->sum('reserved_quantity'),
            'manual_reservations' => $reservations->where('status', 'manual')->sum('reserved_quantity'),
            'partial_reservations' => $reservations->filter(fn($r) => $r->isPartial())->count(),
            'expiring_soon' => $reservations->filter(fn($r) => $r->expires_at && $r->expires_at->diffInMinutes(now()) < 5)->count(),
        ];
    }
}

