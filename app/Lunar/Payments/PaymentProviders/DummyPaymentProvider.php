<?php

namespace App\Lunar\Payments\PaymentProviders;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Base\PaymentTypes\AbstractPayment;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Transaction;

/**
 * Example dummy payment provider for development/testing.
 * 
 * This is a basic implementation for testing purposes. For production,
 * use a proper payment provider or implement CustomPayment.
 * 
 * To register this driver, use Payments::extend() in a service provider:
 * 
 * Payments::extend('dummy', function ($app) {
 *     return $app->make(DummyPaymentProvider::class);
 * });
 * 
 * Then configure it in config/lunar/payments.php:
 * 
 * 'types' => [
 *     'dummy' => [
 *         'driver' => 'dummy',
 *         'released' => 'payment-offline',
 *     ],
 * ],
 * 
 * See: https://docs.lunarphp.com/1.x/extending/payments
 */
class DummyPaymentProvider extends AbstractPayment
{
    /**
     * Authorize the payment.
     * 
     * Dummy payment always succeeds for demo/testing purposes.
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

        // Create a capture transaction (dummy payment charges immediately)
        Transaction::create([
            'order_id' => $this->order->id,
            'success' => true,
            'type' => 'capture', // Dummy payment charges immediately
            'driver' => 'dummy',
            'amount' => $this->order->total->value,
            'reference' => 'DUMMY_' . uniqid(),
            'status' => 'success',
        ]);

        $response = new PaymentAuthorize(
            success: true,
            message: 'Dummy payment successful (for testing only)',
            orderId: $this->order->id,
            paymentType: 'dummy'
        );
        
        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    /**
     * Capture a payment.
     * 
     * Dummy payment doesn't need capturing as it's already captured in authorize().
     *
     * @param Transaction $transaction
     * @param int $amount
     * @return PaymentCapture
     */
    public function capture(Transaction $transaction, int $amount = 0): PaymentCapture
    {
        // Dummy payment is already captured, so just return success
        return new PaymentCapture(true);
    }

    /**
     * Refund a payment.
     * 
     * Dummy refund always succeeds for testing purposes.
     *
     * @param Transaction $transaction
     * @param int $amount
     * @param string|null $notes
     * @return PaymentRefund
     */
    public function refund(Transaction $transaction, int $amount = 0, ?string $notes = null): PaymentRefund
    {
        $amountToRefund = $amount > 0 ? $amount : $transaction->amount;

        // Create refund transaction
        Transaction::create([
            'parent_transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'success' => true,
            'type' => 'refund',
            'driver' => 'dummy',
            'amount' => $amountToRefund,
            'reference' => 'DUMMY_REFUND_' . uniqid(),
            'status' => 'success',
            'notes' => $notes,
        ]);

        return new PaymentRefund(true);
    }
}

