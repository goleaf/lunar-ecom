<?php

namespace App\Lunar\Payments\PaymentProviders;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Base\PaymentTypes\AbstractPayment;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Transaction;

/**
 * Example custom payment driver.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/payments
 * 
 * Payment drivers should take into account 2 fundamentals:
 * - Capturing a payment (whether straight away, or at a later date)
 * - Refunding an existing payment
 */
class CustomPayment extends AbstractPayment
{
    /**
     * Authorize the payment.
     * 
     * This is where you'd check the payment details, create transactions,
     * and return the response. If not taking payment straight away, create
     * a transaction with type 'intent'. If charging immediately, use type 'capture'.
     *
     * @return PaymentAuthorize|null
     */
    public function authorize(): ?PaymentAuthorize
    {
        // Ensure order exists
        if (!$this->order) {
            if (!$this->order = $this->cart->order) {
                $this->order = $this->cart->createOrder();
            }
        }

        // Example: Process payment with external provider
        // $paymentIntent = $this->processPaymentWithProvider();
        
        // Create transaction (use 'intent' if not charging immediately, 'capture' if charging now)
        Transaction::create([
            'order_id' => $this->order->id,
            'success' => true,
            'type' => 'intent', // or 'capture' if charging immediately
            'driver' => 'custom',
            'amount' => $this->order->total->value,
            'reference' => 'CUSTOM_' . uniqid(),
            'status' => 'pending', // or 'success' if immediately successful
            'meta' => [
                // Additional metadata from payment provider
            ],
        ]);

        $response = new PaymentAuthorize(
            success: true,
            message: 'The payment was successful',
            orderId: $this->order->id,
            paymentType: 'custom'
        );
        
        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    /**
     * Capture a payment (charge the card for an intent transaction).
     * 
     * When you have a transaction with type 'intent', staff can capture it
     * to charge the card. Create an additional transaction with type 'capture'
     * that references the intent transaction via parent_transaction_id.
     *
     * @param Transaction $transaction The intent transaction to capture
     * @param int $amount Amount to capture (0 = full amount)
     * @return PaymentCapture
     */
    public function capture(Transaction $transaction, int $amount = 0): PaymentCapture
    {
        $amountToCapture = $amount > 0 ? $amount : $transaction->amount;

        // Example: Capture payment with external provider
        // $captured = $this->capturePaymentWithProvider($transaction->reference, $amountToCapture);

        // Create capture transaction
        Transaction::create([
            'parent_transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'success' => true,
            'type' => 'capture',
            'driver' => 'custom',
            'amount' => $amountToCapture,
            'reference' => 'CUSTOM_CAPTURE_' . uniqid(),
            'status' => 'success',
            'captured_at' => now(),
        ]);

        return new PaymentCapture(true);
    }

    /**
     * Refund a payment.
     * 
     * You can only refund transactions with type 'capture'. Create a new
     * transaction with type 'refund' that references the capture transaction.
     *
     * @param Transaction $transaction The capture transaction to refund
     * @param int $amount Amount to refund (0 = full amount)
     * @param string|null $notes Optional notes for the refund
     * @return PaymentRefund
     */
    public function refund(Transaction $transaction, int $amount = 0, ?string $notes = null): PaymentRefund
    {
        $amountToRefund = $amount > 0 ? $amount : $transaction->amount;

        // Example: Process refund with external provider
        // $refunded = $this->refundPaymentWithProvider($transaction->reference, $amountToRefund);

        // Create refund transaction
        Transaction::create([
            'parent_transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'success' => true,
            'type' => 'refund',
            'driver' => 'custom',
            'amount' => $amountToRefund,
            'reference' => 'CUSTOM_REFUND_' . uniqid(),
            'status' => 'success',
            'notes' => $notes,
        ]);

        return new PaymentRefund(true);
    }
}


