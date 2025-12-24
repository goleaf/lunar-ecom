<?php

namespace App\Lunar\Payments\PaymentProviders;

use Lunar\Base\PaymentTypes\AbstractPayment;
use Lunar\Models\Order;

/**
 * Example dummy payment provider for development/testing.
 * 
 * To use this, register it in config/lunar/payments.php:
 * 
 * 'providers' => [
 *     'dummy' => DummyPaymentProvider::class,
 * ]
 */
class DummyPaymentProvider extends AbstractPayment
{
    /**
     * Authorize the payment.
     */
    public function authorize(): bool
    {
        // Dummy payment always succeeds for demo purposes
        return true;
    }

    /**
     * Refund a payment.
     */
    public function refund(?int $amount = null): bool
    {
        // Implement refund logic here
        return true;
    }
}

