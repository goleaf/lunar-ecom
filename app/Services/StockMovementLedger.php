<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\InventoryLevel;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Stock Movement Ledger Service.
 * 
 * Logs every inventory change with complete audit trail:
 * - Sale
 * - Return
 * - Manual adjustment
 * - Import
 * - Damage
 * - Transfer
 * - Correction
 * 
 * Includes:
 * - Reason
 * - Actor (who made the change)
 * - Timestamp
 * - Before/after quantities
 */
class StockMovementLedger
{
    /**
     * Record a sale movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  mixed  $order
     * @param  string|null  $reason
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordSale(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        $order,
        ?string $reason = null,
        array $metadata = []
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'sale',
            quantity: -$quantity, // Negative for outgoing
            warehouseId: $warehouseId,
            reference: $order,
            reason: $reason ?? ("Sale - Order #" . ($order->reference ?? $order->id)),
            metadata: array_merge($metadata, [
                'order_id' => $order->id,
                'order_reference' => $order->reference ?? null,
            ])
        );
    }

    /**
     * Record a return movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  mixed  $order
     * @param  string|null  $reason
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordReturn(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        $order,
        ?string $reason = null,
        array $metadata = []
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'return',
            quantity: $quantity, // Positive for incoming
            warehouseId: $warehouseId,
            reference: $order,
            reason: $reason ?? ("Return - Order #" . ($order->reference ?? $order->id)),
            metadata: array_merge($metadata, [
                'order_id' => $order->id,
                'return_reason' => $metadata['return_reason'] ?? null,
            ])
        );
    }

    /**
     * Record a manual adjustment.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  string  $reason
     * @param  string|null  $notes
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordManualAdjustment(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        string $reason,
        ?string $notes = null,
        array $metadata = []
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'manual_adjustment',
            quantity: $quantity, // Can be positive or negative
            warehouseId: $warehouseId,
            reference: null,
            reason: $reason,
            notes: $notes,
            metadata: array_merge($metadata, [
                'adjustment_type' => $quantity > 0 ? 'increase' : 'decrease',
            ])
        );
    }

    /**
     * Record an import movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  string|null  $importBatchId
     * @param  string|null  $reason
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordImport(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        ?string $importBatchId = null,
        ?string $reason = null,
        array $metadata = []
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'import',
            quantity: $quantity, // Positive for incoming
            warehouseId: $warehouseId,
            reference: null,
            reason: $reason ?? "Import" . ($importBatchId ? " - Batch #{$importBatchId}" : ''),
            actorType: 'import',
            metadata: array_merge($metadata, [
                'import_batch_id' => $importBatchId,
                'import_source' => $metadata['import_source'] ?? null,
            ])
        );
    }

    /**
     * Record a damage movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  string  $reason
     * @param  string|null  $notes
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordDamage(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        string $reason,
        ?string $notes = null,
        array $metadata = []
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'damage',
            quantity: -$quantity, // Negative for outgoing
            warehouseId: $warehouseId,
            reference: null,
            reason: $reason,
            notes: $notes,
            metadata: array_merge($metadata, [
                'damage_type' => $metadata['damage_type'] ?? null,
                'damage_severity' => $metadata['damage_severity'] ?? null,
            ])
        );
    }

    /**
     * Record a transfer movement (between warehouses).
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $fromWarehouseId
     * @param  int  $toWarehouseId
     * @param  string|null  $transferId
     * @param  string|null  $reason
     * @param  array  $metadata
     * @return array Returns both movements [outbound, inbound]
     */
    public function recordTransfer(
        ProductVariant $variant,
        int $quantity,
        int $fromWarehouseId,
        int $toWarehouseId,
        ?string $transferId = null,
        ?string $reason = null,
        array $metadata = []
    ): array {
        return DB::transaction(function () use (
            $variant,
            $quantity,
            $fromWarehouseId,
            $toWarehouseId,
            $transferId,
            $reason,
            $metadata
        ) {
            // Outbound movement (from warehouse)
            $outbound = $this->recordMovement(
                variant: $variant,
                type: 'transfer',
                quantity: -$quantity, // Negative for outgoing
                warehouseId: $fromWarehouseId,
                reference: null,
                reason: $reason ?? "Transfer to Warehouse #{$toWarehouseId}" . ($transferId ? " - Transfer #{$transferId}" : ''),
                metadata: array_merge($metadata, [
                    'transfer_id' => $transferId,
                    'transfer_direction' => 'outbound',
                    'to_warehouse_id' => $toWarehouseId,
                ])
            );

            // Inbound movement (to warehouse)
            $inbound = $this->recordMovement(
                variant: $variant,
                type: 'transfer',
                quantity: $quantity, // Positive for incoming
                warehouseId: $toWarehouseId,
                reference: null,
                reason: $reason ?? "Transfer from Warehouse #{$fromWarehouseId}" . ($transferId ? " - Transfer #{$transferId}" : ''),
                metadata: array_merge($metadata, [
                    'transfer_id' => $transferId,
                    'transfer_direction' => 'inbound',
                    'from_warehouse_id' => $fromWarehouseId,
                ])
            );

            return [$outbound, $inbound];
        });
    }

    /**
     * Record a correction movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantityBefore
     * @param  int  $quantityAfter
     * @param  int  $warehouseId
     * @param  string  $reason
     * @param  string|null  $notes
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordCorrection(
        ProductVariant $variant,
        int $quantityBefore,
        int $quantityAfter,
        int $warehouseId,
        string $reason,
        ?string $notes = null,
        array $metadata = []
    ): StockMovement {
        $quantity = $quantityAfter - $quantityBefore;

        return $this->recordMovement(
            variant: $variant,
            type: 'correction',
            quantity: $quantity,
            warehouseId: $warehouseId,
            reference: null,
            reason: $reason,
            notes: $notes,
            metadata: array_merge($metadata, [
                'correction_type' => $quantity > 0 ? 'increase' : 'decrease',
                'original_quantity' => $quantityBefore,
                'corrected_quantity' => $quantityAfter,
            ])
        );
    }

    /**
     * Record a reservation movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  mixed  $reservation
     * @param  string|null  $reason
     * @return StockMovement
     */
    public function recordReservation(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        $reservation,
        ?string $reason = null
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'reservation',
            quantity: -$quantity, // Negative (reserved, not available)
            warehouseId: $warehouseId,
            reference: $reservation,
            reason: $reason ?? "Stock reservation",
            metadata: [
                'reservation_id' => $reservation->id ?? null,
                'reservation_type' => get_class($reservation),
            ]
        );
    }

    /**
     * Record a reservation release movement.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  mixed  $reservation
     * @param  string|null  $reason
     * @return StockMovement
     */
    public function recordReservationRelease(
        ProductVariant $variant,
        int $quantity,
        int $warehouseId,
        $reservation,
        ?string $reason = null
    ): StockMovement {
        return $this->recordMovement(
            variant: $variant,
            type: 'release',
            quantity: $quantity, // Positive (released back to available)
            warehouseId: $warehouseId,
            reference: $reservation,
            reason: $reason ?? "Reservation released",
            metadata: [
                'reservation_id' => $reservation->id ?? null,
                'release_reason' => $reason,
            ]
        );
    }

    /**
     * Core method to record any stock movement.
     *
     * @param  ProductVariant  $variant
     * @param  string  $type
     * @param  int  $quantity
     * @param  int  $warehouseId
     * @param  mixed  $reference
     * @param  string|null  $reason
     * @param  string|null  $notes
     * @param  string  $actorType
     * @param  string|null  $actorIdentifier
     * @param  array  $metadata
     * @return StockMovement
     */
    public function recordMovement(
        ProductVariant $variant,
        string $type,
        int $quantity,
        int $warehouseId,
        $reference = null,
        ?string $reason = null,
        ?string $notes = null,
        string $actorType = 'user',
        ?string $actorIdentifier = null,
        array $metadata = []
    ): StockMovement {
        return DB::transaction(function () use (
            $variant,
            $type,
            $quantity,
            $warehouseId,
            $reference,
            $reason,
            $notes,
            $actorType,
            $actorIdentifier,
            $metadata
        ) {
            // Get inventory level
            $inventoryLevel = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventoryLevel) {
                // Create inventory level if it doesn't exist
                $inventoryLevel = InventoryLevel::create([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'status' => 'out_of_stock',
                ]);
            }

            // Capture before state
            $quantityBefore = $inventoryLevel->quantity;
            $reservedBefore = $inventoryLevel->reserved_quantity;
            $availableBefore = $inventoryLevel->available_quantity;

            // Update inventory level based on movement type
            if (in_array($type, ['sale', 'damage', 'loss', 'transfer']) || $quantity < 0) {
                // Decrease on-hand quantity
                $inventoryLevel->decrement('quantity', abs($quantity));
            } elseif (in_array($type, ['return', 'import', 'manual_adjustment', 'correction']) || $quantity > 0) {
                // Increase on-hand quantity
                $inventoryLevel->increment('quantity', abs($quantity));
            } elseif ($type === 'reservation') {
                // Increase reserved quantity
                $inventoryLevel->increment('reserved_quantity', abs($quantity));
            } elseif ($type === 'release') {
                // Decrease reserved quantity
                $inventoryLevel->decrement('reserved_quantity', abs($quantity));
            }

            // Refresh to get updated values
            $inventoryLevel->refresh();
            $inventoryLevel->updateStatus();
            $inventoryLevel->save();

            // Capture after state
            $quantityAfter = $inventoryLevel->quantity;
            $reservedAfter = $inventoryLevel->reserved_quantity;
            $availableAfter = $inventoryLevel->available_quantity;

            // Determine reference information
            $referenceType = $reference ? get_class($reference) : null;
            $referenceId = $reference?->id ?? null;
            $referenceNumber = $this->getReferenceNumber($reference);

            // Get actor information
            $userId = null;
            if ($actorType === 'user') {
                $userId = Auth::id();
            }

            // Create movement record
            return StockMovement::create([
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId,
                'inventory_level_id' => $inventoryLevel->id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reserved_quantity_before' => $reservedBefore,
                'reserved_quantity_after' => $reservedAfter,
                'available_quantity_before' => $availableBefore,
                'available_quantity_after' => $availableAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'reason' => $reason,
                'notes' => $notes,
                'metadata' => $metadata,
                'created_by' => $userId,
                'actor_type' => $actorType,
                'actor_identifier' => $actorIdentifier,
                'ip_address' => request()->ip(),
                'movement_date' => now(),
            ]);
        });
    }

    /**
     * Get reference number from reference object.
     *
     * @param  mixed  $reference
     * @return string|null
     */
    protected function getReferenceNumber($reference): ?string
    {
        if (!$reference) {
            return null;
        }

        // Try common methods
        if (method_exists($reference, 'getReference')) {
            return $reference->getReference();
        }

        if (isset($reference->reference)) {
            return $reference->reference;
        }

        if (isset($reference->reference_number)) {
            return $reference->reference_number;
        }

        if (isset($reference->id)) {
            return (string) $reference->id;
        }

        return null;
    }

    /**
     * Get movement ledger for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @param  \DateTimeInterface|null  $from
     * @param  \DateTimeInterface|null  $to
     * @param  string|null  $type
     * @return \Illuminate\Support\Collection
     */
    public function getLedger(
        ProductVariant $variant,
        ?int $warehouseId = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        ?string $type = null
    ): \Illuminate\Support\Collection {
        $query = StockMovement::where('product_variant_id', $variant->id)
            ->with(['creator', 'warehouse', 'reference']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($from) {
            $query->where('movement_date', '>=', $from);
        }

        if ($to) {
            $query->where('movement_date', '<=', $to);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderByDesc('movement_date')->get();
    }

    /**
     * Get ledger summary.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @param  \DateTimeInterface|null  $from
     * @param  \DateTimeInterface|null  $to
     * @return array
     */
    public function getLedgerSummary(
        ProductVariant $variant,
        ?int $warehouseId = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $movements = $this->getLedger($variant, $warehouseId, $from, $to);

        return [
            'total_movements' => $movements->count(),
            'total_in' => $movements->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => abs($movements->where('quantity', '<', 0)->sum('quantity')),
            'by_type' => $movements->groupBy('type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_quantity' => $group->sum('quantity'),
                ];
            }),
            'by_actor' => $movements->groupBy('created_by')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'actor_name' => $group->first()->actor_name ?? 'Unknown',
                ];
            }),
        ];
    }
}

