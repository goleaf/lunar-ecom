<?php

namespace App\Services;

use App\Models\PaymentFingerprint;
use Lunar\Models\Order;
use Lunar\Models\Transaction;

/**
 * Payment Fingerprint Service
 * 
 * Creates payment fingerprints from tokenized card identifiers.
 */
class PaymentFingerprintService
{
    /**
     * Generate payment fingerprint from order/transaction.
     */
    public function generateFingerprint(Order $order): ?string
    {
        // Get payment transaction
        $transaction = $order->transactions()
            ->where('type', 'capture')
            ->where('success', true)
            ->first();

        if (!$transaction) {
            return null;
        }

        // Get card details from transaction metadata
        $metadata = $transaction->meta ?? [];
        
        $components = [
            $metadata['card_last4'] ?? null,
            $metadata['card_brand'] ?? null,
            $metadata['card_country'] ?? null,
            $metadata['card_fingerprint'] ?? null, // If payment gateway provides fingerprint
        ];

        // If no fingerprint from gateway, create one from available data
        if (empty($components[3])) {
            $components = array_filter($components);
            if (empty($components)) {
                return null;
            }
        }

        // Create hash
        $fingerprint = hash('sha256', implode('|', $components));

        return $fingerprint;
    }

    /**
     * Store payment fingerprint.
     */
    public function storeFingerprint(string $fingerprintHash, Order $order): PaymentFingerprint
    {
        $transaction = $order->transactions()
            ->where('type', 'capture')
            ->where('success', true)
            ->first();

        $metadata = $transaction->meta ?? [];

        return PaymentFingerprint::firstOrCreate(
            ['fingerprint_hash' => $fingerprintHash],
            [
                'card_last4' => $metadata['card_last4'] ?? null,
                'card_brand' => $metadata['card_brand'] ?? null,
                'card_country' => $metadata['card_country'] ?? null,
                'metadata' => [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id ?? null,
                    'created_at' => now()->toIso8601String(),
                ],
            ]
        );
    }
}


