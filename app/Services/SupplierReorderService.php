<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\SupplierReorderHook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Supplier Reorder Service.
 * 
 * Handles supplier reorder automation:
 * - API integration
 * - Email-based reorders
 * - Webhook integration
 * - CSV export
 * - ERP integration
 */
class SupplierReorderService
{
    /**
     * Trigger reorder for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @param  int|null  $warehouseId
     * @return bool
     */
    public function triggerReorder(
        ProductVariant $variant,
        int $currentQuantity,
        ?int $warehouseId = null
    ): bool {
        $hooks = SupplierReorderHook::where('product_variant_id', $variant->id)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->active()
            ->get();

        $success = false;

        foreach ($hooks as $hook) {
            if ($hook->shouldTriggerReorder($currentQuantity)) {
                $result = $this->processReorder($hook, $variant, $currentQuantity);
                if ($result) {
                    $success = true;
                }
            }
        }

        return $success;
    }

    /**
     * Process reorder for a hook.
     *
     * @param  SupplierReorderHook  $hook
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @return bool
     */
    protected function processReorder(
        SupplierReorderHook $hook,
        ProductVariant $variant,
        int $currentQuantity
    ): bool {
        return match($hook->integration_type) {
            'api' => $this->processApiReorder($hook, $variant, $currentQuantity),
            'email' => $this->processEmailReorder($hook, $variant, $currentQuantity),
            'webhook' => $this->processWebhookReorder($hook, $variant, $currentQuantity),
            'csv_export' => $this->processCsvExportReorder($hook, $variant, $currentQuantity),
            'erp' => $this->processErpReorder($hook, $variant, $currentQuantity),
            default => false,
        };
    }

    /**
     * Process API reorder.
     *
     * @param  SupplierReorderHook  $hook
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @return bool
     */
    protected function processApiReorder(
        SupplierReorderHook $hook,
        ProductVariant $variant,
        int $currentQuantity
    ): bool {
        $config = $hook->integration_config ?? [];
        $endpoint = $config['endpoint'] ?? null;
        $apiKey = $config['api_key'] ?? null;

        if (!$endpoint || !$apiKey) {
            Log::warning("Supplier reorder hook {$hook->id} missing API configuration");
            return false;
        }

        try {
            $quantity = $this->calculateReorderQuantity($hook, $currentQuantity);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'supplier_sku' => $hook->supplier_sku ?? $variant->sku,
                'quantity' => $quantity,
                'unit_cost' => $hook->unit_cost,
                'warehouse_id' => $hook->warehouse_id,
                'variant_id' => $variant->id,
            ]);

            if ($response->successful()) {
                $hook->update([
                    'last_reorder_at' => now(),
                    'reorder_count' => $hook->reorder_count + 1,
                    'last_reorder_response' => $response->body(),
                ]);

                return true;
            }

            Log::error("Supplier reorder API failed: {$response->body()}");
            return false;
        } catch (\Exception $e) {
            Log::error("Supplier reorder API error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Process email reorder.
     *
     * @param  SupplierReorderHook  $hook
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @return bool
     */
    protected function processEmailReorder(
        SupplierReorderHook $hook,
        ProductVariant $variant,
        int $currentQuantity
    ): bool {
        $config = $hook->integration_config ?? [];
        $email = $config['email'] ?? null;

        if (!$email) {
            Log::warning("Supplier reorder hook {$hook->id} missing email configuration");
            return false;
        }

        try {
            $quantity = $this->calculateReorderQuantity($hook, $currentQuantity);

            Mail::send('emails.supplier-reorder', [
                'hook' => $hook,
                'variant' => $variant,
                'quantity' => $quantity,
                'currentQuantity' => $currentQuantity,
            ], function ($message) use ($email, $variant) {
                $message->to($email)
                    ->subject("Reorder Request: {$variant->sku}");
            });

            $hook->update([
                'last_reorder_at' => now(),
                'reorder_count' => $hook->reorder_count + 1,
                'last_reorder_response' => 'Email sent',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Supplier reorder email error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Process webhook reorder.
     *
     * @param  SupplierReorderHook  $hook
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @return bool
     */
    protected function processWebhookReorder(
        SupplierReorderHook $hook,
        ProductVariant $variant,
        int $currentQuantity
    ): bool {
        $config = $hook->integration_config ?? [];
        $webhookUrl = $config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            Log::warning("Supplier reorder hook {$hook->id} missing webhook URL");
            return false;
        }

        try {
            $quantity = $this->calculateReorderQuantity($hook, $currentQuantity);

            $response = Http::post($webhookUrl, [
                'supplier_sku' => $hook->supplier_sku ?? $variant->sku,
                'quantity' => $quantity,
                'unit_cost' => $hook->unit_cost,
                'warehouse_id' => $hook->warehouse_id,
                'variant_id' => $variant->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($response->successful()) {
                $hook->update([
                    'last_reorder_at' => now(),
                    'reorder_count' => $hook->reorder_count + 1,
                    'last_reorder_response' => $response->body(),
                ]);

                return true;
            }

            Log::error("Supplier reorder webhook failed: {$response->body()}");
            return false;
        } catch (\Exception $e) {
            Log::error("Supplier reorder webhook error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Process CSV export reorder.
     *
     * @param  SupplierReorderHook  $hook
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @return bool
     */
    protected function processCsvExportReorder(
        SupplierReorderHook $hook,
        ProductVariant $variant,
        int $currentQuantity
    ): bool {
        // CSV export would typically be handled by a scheduled job
        // that exports all pending reorders to a CSV file
        // For now, we'll just log it
        Log::info("CSV export reorder queued for variant {$variant->id}");

        $hook->update([
            'last_reorder_at' => now(),
            'reorder_count' => $hook->reorder_count + 1,
            'last_reorder_response' => 'CSV export queued',
        ]);

        return true;
    }

    /**
     * Process ERP reorder.
     *
     * @param  SupplierReorderHook  $hook
     * @param  ProductVariant  $variant
     * @param  int  $currentQuantity
     * @return bool
     */
    protected function processErpReorder(
        SupplierReorderHook $hook,
        ProductVariant $variant,
        int $currentQuantity
    ): bool {
        // ERP integration would typically use a specific ERP connector
        // This is a placeholder for ERP-specific logic
        $config = $hook->integration_config ?? [];
        
        Log::info("ERP reorder queued for variant {$variant->id}", [
            'erp_system' => $config['erp_system'] ?? 'unknown',
        ]);

        $hook->update([
            'last_reorder_at' => now(),
            'reorder_count' => $hook->reorder_count + 1,
            'last_reorder_response' => 'ERP reorder queued',
        ]);

        return true;
    }

    /**
     * Calculate reorder quantity.
     *
     * @param  SupplierReorderHook  $hook
     * @param  int  $currentQuantity
     * @return int
     */
    protected function calculateReorderQuantity(
        SupplierReorderHook $hook,
        int $currentQuantity
    ): int {
        $quantity = $hook->reorder_quantity ?? 0;

        // Apply min/max constraints
        if ($hook->min_order_quantity && $quantity < $hook->min_order_quantity) {
            $quantity = $hook->min_order_quantity;
        }

        if ($hook->max_order_quantity && $quantity > $hook->max_order_quantity) {
            $quantity = $hook->max_order_quantity;
        }

        return $quantity;
    }
}


