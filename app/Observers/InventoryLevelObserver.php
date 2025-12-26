<?php

namespace App\Observers;

use App\Models\InventoryLevel;
use App\Services\InventoryAutomationService;
use App\Services\StockMovementLedger;

/**
 * Inventory Level Observer.
 * 
 * Observes inventory level changes and triggers automation:
 * - Out of stock triggers
 * - Restock triggers
 * - Low stock alerts
 */
class InventoryLevelObserver
{
    protected InventoryAutomationService $automationService;
    protected StockMovementLedger $ledger;

    public function __construct(
        InventoryAutomationService $automationService,
        StockMovementLedger $ledger
    ) {
        $this->automationService = $automationService;
        $this->ledger = $ledger;
    }

    /**
     * Handle the InventoryLevel "saved" event.
     *
     * @param  InventoryLevel  $inventoryLevel
     * @return void
     */
    public function saved(InventoryLevel $inventoryLevel): void
    {
        $variant = $inventoryLevel->productVariant;
        if (!$variant) {
            return;
        }

        // Get previous quantity if available
        $quantityBefore = $inventoryLevel->getOriginal('quantity') ?? 0;
        $quantityAfter = $inventoryLevel->quantity;
        $availableBefore = $inventoryLevel->getOriginal('available_quantity') ?? 0;
        $availableAfter = $inventoryLevel->available_quantity;

        // Check if went out of stock
        if ($availableBefore > 0 && $availableAfter <= 0) {
            $this->automationService->handleOutOfStock(
                variant: $variant,
                quantityBefore: $availableBefore,
                quantityAfter: $availableAfter,
                reason: 'inventory_change',
                warehouseId: $inventoryLevel->warehouse_id
            );
        }

        // Check if restocked (was out of stock, now has stock)
        if ($availableBefore <= 0 && $availableAfter > 0) {
            $this->automationService->handleRestock(
                variant: $variant,
                quantity: $availableAfter,
                reason: 'restock',
                warehouseId: $inventoryLevel->warehouse_id
            );
        }

        // Process automation rules
        $this->automationService->processAutomation($variant, $inventoryLevel->warehouse_id);
    }
}
