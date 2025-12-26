<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\InventoryAutomationRule;
use App\Models\OutOfStockTrigger;
use App\Models\LowStockAlert;
use App\Models\SupplierReorderHook;
use App\Services\VariantInventoryEngine;
use App\Services\VariantLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Automation Service.
 * 
 * Handles:
 * - Low-stock alerts
 * - Out-of-stock triggers
 * - Auto-disable variants
 * - Auto-enable on restock
 * - Supplier reorder hooks
 */
class InventoryAutomationService
{
    protected VariantInventoryEngine $inventoryEngine;
    protected VariantLifecycleService $lifecycleService;

    public function __construct(
        VariantInventoryEngine $inventoryEngine,
        VariantLifecycleService $lifecycleService
    ) {
        $this->inventoryEngine = $inventoryEngine;
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Process automation rules for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return array
     */
    public function processAutomation(ProductVariant $variant, ?int $warehouseId = null): array
    {
        $results = [
            'alerts_created' => 0,
            'variants_disabled' => 0,
            'variants_enabled' => 0,
            'reorders_created' => 0,
            'triggers_created' => 0,
        ];

        // Get inventory summary
        $inventory = $this->inventoryEngine->getInventorySummary($variant, $warehouseId);
        $availableQuantity = $inventory['available_quantity'];

        // Get applicable automation rules
        $rules = InventoryAutomationRule::where(function ($query) use ($variant) {
            $query->where('product_variant_id', $variant->id)
                  ->orWhere('product_id', $variant->product_id);
        })
        ->active()
        ->orderByDesc('priority')
        ->get();

        foreach ($rules as $rule) {
            if (!$rule->canTrigger()) {
                continue;
            }

            // Check if trigger condition is met
            if ($this->checkTriggerCondition($rule, $variant, $availableQuantity, $inventory)) {
                $actionResult = $this->executeAction($rule, $variant, $availableQuantity, $inventory);
                
                // Update results
                $results['alerts_created'] += $actionResult['alerts_created'] ?? 0;
                $results['variants_disabled'] += $actionResult['variants_disabled'] ?? 0;
                $results['variants_enabled'] += $actionResult['variants_enabled'] ?? 0;
                $results['reorders_created'] += $actionResult['reorders_created'] ?? 0;
                $results['triggers_created'] += $actionResult['triggers_created'] ?? 0;

                // Mark rule as triggered
                $rule->markTriggered();
            }
        }

        return $results;
    }

    /**
     * Check if trigger condition is met.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @return bool
     */
    protected function checkTriggerCondition(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory
    ): bool {
        return match($rule->trigger_type) {
            'low_stock' => $this->checkLowStockTrigger($rule, $variant, $availableQuantity),
            'out_of_stock' => $availableQuantity <= 0,
            'restock' => $this->checkRestockTrigger($rule, $variant, $availableQuantity),
            'below_safety_stock' => $this->checkBelowSafetyStockTrigger($rule, $variant, $inventory),
            'custom' => $this->checkCustomTrigger($rule, $variant, $availableQuantity, $inventory),
            default => false,
        };
    }

    /**
     * Check low stock trigger.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @return bool
     */
    protected function checkLowStockTrigger(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        int $availableQuantity
    ): bool {
        $threshold = $rule->trigger_conditions['threshold'] ?? $variant->low_stock_threshold ?? 10;
        return $availableQuantity <= $threshold;
    }

    /**
     * Check restock trigger.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @return bool
     */
    protected function checkRestockTrigger(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        int $availableQuantity
    ): bool {
        // Check if variant was previously out of stock
        $wasOutOfStock = OutOfStockTrigger::where('product_variant_id', $variant->id)
            ->unrecovered()
            ->exists();

        return $wasOutOfStock && $availableQuantity > 0;
    }

    /**
     * Check below safety stock trigger.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  array  $inventory
     * @return bool
     */
    protected function checkBelowSafetyStockTrigger(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        array $inventory
    ): bool {
        return $inventory['is_below_safety_stock'] ?? false;
    }

    /**
     * Check custom trigger.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @return bool
     */
    protected function checkCustomTrigger(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory
    ): bool {
        // Custom trigger logic can be implemented here
        // For now, return false
        return false;
    }

    /**
     * Execute automation action.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @return array
     */
    protected function executeAction(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory
    ): array {
        $results = [
            'alerts_created' => 0,
            'variants_disabled' => 0,
            'variants_enabled' => 0,
            'reorders_created' => 0,
            'triggers_created' => 0,
        ];

        return match($rule->action_type) {
            'disable_variant' => $this->disableVariant($variant, $results),
            'enable_variant' => $this->enableVariant($variant, $results),
            'hide_variant' => $this->hideVariant($variant, $results),
            'show_variant' => $this->showVariant($variant, $results),
            'send_alert' => $this->sendAlert($variant, $availableQuantity, $inventory, $results),
            'create_reorder' => $this->createReorder($variant, $availableQuantity, $inventory, $results),
            'custom' => $this->executeCustomAction($rule, $variant, $availableQuantity, $inventory, $results),
            default => $results,
        };
    }

    /**
     * Disable variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $results
     * @return array
     */
    protected function disableVariant(ProductVariant $variant, array $results): array
    {
        if ($variant->status !== 'inactive') {
            $this->lifecycleService->deactivate($variant, 'Auto-disabled: Out of stock');
            $results['variants_disabled'] = 1;
        }

        return $results;
    }

    /**
     * Enable variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $results
     * @return array
     */
    protected function enableVariant(ProductVariant $variant, array $results): array
    {
        if ($variant->status === 'inactive') {
            $this->lifecycleService->activate($variant, 'Auto-enabled: Restocked');
            $results['variants_enabled'] = 1;
        }

        return $results;
    }

    /**
     * Hide variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $results
     * @return array
     */
    protected function hideVariant(ProductVariant $variant, array $results): array
    {
        $variant->update(['visibility' => 'hidden']);
        return $results;
    }

    /**
     * Show variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $results
     * @return array
     */
    protected function showVariant(ProductVariant $variant, array $results): array
    {
        $variant->update(['visibility' => 'public']);
        return $results;
    }

    /**
     * Send alert.
     *
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @param  array  $results
     * @return array
     */
    protected function sendAlert(
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory,
        array $results
    ): array {
        // Create low stock alert if applicable
        if ($availableQuantity <= ($variant->low_stock_threshold ?? 10)) {
            $this->createLowStockAlert($variant, $availableQuantity, $inventory);
            $results['alerts_created'] = 1;
        }

        return $results;
    }

    /**
     * Create reorder.
     *
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @param  array  $results
     * @return array
     */
    protected function createReorder(
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory,
        array $results
    ): array {
        $reorderService = app(\App\Services\SupplierReorderService::class);
        $reorderCreated = $reorderService->triggerReorder($variant, $availableQuantity);
        if ($reorderCreated) {
            $results['reorders_created'] = 1;
        }

        return $results;
    }

    /**
     * Execute custom action.
     *
     * @param  InventoryAutomationRule  $rule
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @param  array  $results
     * @return array
     */
    protected function executeCustomAction(
        InventoryAutomationRule $rule,
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory,
        array $results
    ): array {
        // Custom action logic can be implemented here
        // For now, return results unchanged
        return $results;
    }

    /**
     * Handle out of stock event.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantityBefore
     * @param  int  $quantityAfter
     * @param  string  $reason
     * @param  int|null  $warehouseId
     * @return OutOfStockTrigger
     */
    public function handleOutOfStock(
        ProductVariant $variant,
        int $quantityBefore,
        int $quantityAfter,
        string $reason = 'sale',
        ?int $warehouseId = null
    ): OutOfStockTrigger {
        return DB::transaction(function () use ($variant, $quantityBefore, $quantityAfter, $reason, $warehouseId) {
            // Create out of stock trigger
            $trigger = OutOfStockTrigger::create([
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId,
                'triggered_at' => now(),
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'trigger_reason' => $reason,
            ]);

            // Process automation rules
            $automationResults = $this->processAutomation($variant, $warehouseId);
            
            $trigger->update([
                'automation_triggered' => true,
                'automation_actions' => $automationResults,
            ]);

            return $trigger;
        });
    }

    /**
     * Handle restock event.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  string  $reason
     * @param  int|null  $warehouseId
     * @return void
     */
    public function handleRestock(
        ProductVariant $variant,
        int $quantity,
        string $reason = 'restock',
        ?int $warehouseId = null
    ): void {
        DB::transaction(function () use ($variant, $quantity, $reason, $warehouseId) {
            // Mark unrecovered out-of-stock triggers as recovered
            OutOfStockTrigger::where('product_variant_id', $variant->id)
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->unrecovered()
                ->get()
                ->each(function ($trigger) use ($quantity, $reason) {
                    $trigger->markRecovered($quantity, $reason);
                });

            // Process automation rules (may enable variant)
            $this->processAutomation($variant, $warehouseId);
        });
    }

    /**
     * Create low stock alert.
     *
     * @param  ProductVariant  $variant
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @return LowStockAlert|null
     */
    protected function createLowStockAlert(
        ProductVariant $variant,
        int $availableQuantity,
        array $inventory
    ): ?LowStockAlert {
        // Check if alert already exists
        $existingAlert = LowStockAlert::where('product_variant_id', $variant->id)
            ->where('is_resolved', false)
            ->first();

        if ($existingAlert) {
            // Update existing alert
            $existingAlert->update([
                'current_quantity' => $availableQuantity,
            ]);
            return $existingAlert;
        }

        // Create new alert
        // Note: This assumes LowStockAlert model exists and has these fields
        // You may need to adjust based on your actual LowStockAlert model structure
        return LowStockAlert::create([
            'product_variant_id' => $variant->id,
            'current_quantity' => $availableQuantity,
            'reorder_point' => $variant->low_stock_threshold ?? 10,
            'is_resolved' => false,
        ]);
    }
}

