<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantSerialNumber;
use App\Models\VariantLot;
use App\Models\VariantLicenseKey;
use App\Models\VariantPersonalization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service for managing advanced variant features.
 * 
 * Handles:
 * - Serial number tracking
 * - Expiry dates
 * - Lot/batch tracking
 * - Subscription variants
 * - Digital-only variants
 * - License key management
 * - Variant personalization
 */
class VariantAdvancedFeaturesService
{
    /**
     * Generate and assign serial numbers to variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  array  $serialNumbers
     * @return \Illuminate\Support\Collection
     */
    public function generateSerialNumbers(ProductVariant $variant, int $quantity, array $serialNumbers = []): \Illuminate\Support\Collection
    {
        $generated = collect();

        for ($i = 0; $i < $quantity; $i++) {
            $serialNumber = $serialNumbers[$i] ?? $this->generateSerialNumber($variant);
            
            $generated->push(VariantSerialNumber::create([
                'product_variant_id' => $variant->id,
                'serial_number' => $serialNumber,
                'status' => 'available',
            ]));
        }

        return $generated;
    }

    /**
     * Generate serial number.
     *
     * @param  ProductVariant  $variant
     * @return string
     */
    protected function generateSerialNumber(ProductVariant $variant): string
    {
        $prefix = strtoupper(substr($variant->sku ?? 'VAR', 0, 3));
        return $prefix . '-' . strtoupper(Str::random(8));
    }

    /**
     * Allocate serial number to order.
     *
     * @param  ProductVariant  $variant
     * @param  int  $orderLineId
     * @return VariantSerialNumber|null
     */
    public function allocateSerialNumber(ProductVariant $variant, int $orderLineId): ?VariantSerialNumber
    {
        $serialNumber = VariantSerialNumber::where('product_variant_id', $variant->id)
            ->available()
            ->first();

        if ($serialNumber) {
            $serialNumber->allocate($orderLineId);
            return $serialNumber;
        }

        return null;
    }

    /**
     * Create lot/batch for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return VariantLot
     */
    public function createLot(ProductVariant $variant, array $data): VariantLot
    {
        return VariantLot::create([
            'product_variant_id' => $variant->id,
            'lot_number' => $data['lot_number'] ?? $this->generateLotNumber($variant),
            'batch_number' => $data['batch_number'] ?? null,
            'manufacture_date' => $data['manufacture_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Generate lot number.
     *
     * @param  ProductVariant  $variant
     * @return string
     */
    protected function generateLotNumber(ProductVariant $variant): string
    {
        return 'LOT-' . strtoupper(Str::random(6)) . '-' . now()->format('Ymd');
    }

    /**
     * Allocate quantity from lot.
     *
     * @param  VariantLot  $lot
     * @param  int  $quantity
     * @return bool
     */
    public function allocateFromLot(VariantLot $lot, int $quantity): bool
    {
        return $lot->allocate($quantity);
    }

    /**
     * Generate license keys for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function generateLicenseKeys(ProductVariant $variant, int $quantity, array $options = []): \Illuminate\Support\Collection
    {
        $format = $options['format'] ?? 'XXXX-XXXX-XXXX-XXXX';
        $expiryDate = $options['expiry_date'] ?? null;
        $maxActivations = $options['max_activations'] ?? 1;

        $keys = collect();

        for ($i = 0; $i < $quantity; $i++) {
            $keys->push(VariantLicenseKey::create([
                'product_variant_id' => $variant->id,
                'license_key' => VariantLicenseKey::generate($format),
                'status' => 'available',
                'expiry_date' => $expiryDate,
                'max_activations' => $maxActivations,
            ]));
        }

        return $keys;
    }

    /**
     * Allocate license key to order.
     *
     * @param  ProductVariant  $variant
     * @param  int  $orderLineId
     * @return VariantLicenseKey|null
     */
    public function allocateLicenseKey(ProductVariant $variant, int $orderLineId): ?VariantLicenseKey
    {
        $licenseKey = VariantLicenseKey::where('product_variant_id', $variant->id)
            ->available()
            ->first();

        if ($licenseKey) {
            $licenseKey->update([
                'order_line_id' => $orderLineId,
                'status' => 'allocated',
                'allocated_at' => now(),
            ]);
            return $licenseKey;
        }

        return null;
    }

    /**
     * Save personalization data.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $orderLineId
     * @param  array  $personalizations
     * @return \Illuminate\Support\Collection
     */
    public function savePersonalizations(ProductVariant $variant, ?int $orderLineId, array $personalizations): \Illuminate\Support\Collection
    {
        $saved = collect();

        foreach ($personalizations as $fieldName => $data) {
            $saved->push(VariantPersonalization::create([
                'product_variant_id' => $variant->id,
                'order_line_id' => $orderLineId,
                'field_name' => $fieldName,
                'field_type' => $data['type'] ?? 'text',
                'field_value' => $data['value'] ?? null,
                'field_options' => $data['options'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]));
        }

        return $saved;
    }

    /**
     * Get personalization fields for variant.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getPersonalizationFields(ProductVariant $variant): array
    {
        return $variant->personalization_fields ?? [];
    }

    /**
     * Check if variant requires serial number tracking.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function requiresSerialNumberTracking(ProductVariant $variant): bool
    {
        return VariantSerialNumber::where('product_variant_id', $variant->id)->exists();
    }

    /**
     * Check if variant requires lot tracking.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function requiresLotTracking(ProductVariant $variant): bool
    {
        return $variant->requires_lot_tracking ?? false;
    }

    /**
     * Check if variant is digital.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function isDigital(ProductVariant $variant): bool
    {
        return $variant->is_digital ?? false;
    }

    /**
     * Check if variant is subscription.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function isSubscription(ProductVariant $variant): bool
    {
        return $variant->is_subscription ?? false;
    }

    /**
     * Get subscription interval.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getSubscriptionInterval(ProductVariant $variant): array
    {
        return [
            'interval' => $variant->subscription_interval ?? 'monthly',
            'interval_count' => $variant->subscription_interval_count ?? 1,
            'trial_days' => $variant->subscription_trial_days ?? null,
        ];
    }
}


