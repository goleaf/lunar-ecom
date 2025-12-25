<?php

namespace App\Lunar\Payments;

use Illuminate\Support\Collection;
use Lunar\Facades\Payments;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Transaction;

/**
 * Helper class for working with Lunar Payments.
 * 
 * Provides convenience methods for processing payments using payment drivers.
 * See: https://docs.lunarphp.com/1.x/reference/payments
 */
class PaymentHelper
{
    /**
     * Get a payment driver instance.
     * 
     * @param string $type Payment type (e.g., 'card', 'cash-in-hand')
     * @return \Lunar\Base\PaymentTypes\AbstractPayment
     */
    public static function driver(string $type)
    {
        return Payments::driver($type);
    }

    /**
     * Get the default payment driver.
     * 
     * @return \Lunar\Base\PaymentTypes\AbstractPayment
     */
    public static function defaultDriver()
    {
        $defaultType = config('lunar.payments.default', 'offline');
        return Payments::driver($defaultType);
    }

    /**
     * Process a payment for a cart.
     * 
     * @param Cart $cart
     * @param string $type Payment type
     * @param array $data Additional data for the payment driver (e.g., payment_token)
     * @return \Lunar\Base\DataTransferObjects\PaymentAuthorize
     */
    public static function processPayment(Cart $cart, string $type, array $data = [])
    {
        $driver = Payments::driver($type);
        
        $driver->cart($cart);
        
        if (!empty($data)) {
            $driver->withData($data);
        }
        
        return $driver->authorize();
    }

    /**
     * Process a payment with a payment token (e.g., Stripe token).
     * 
     * @param Cart $cart
     * @param string $type Payment type
     * @param string $paymentToken Payment token from payment provider
     * @param array $additionalData Additional data for the payment driver
     * @return \Lunar\Base\DataTransferObjects\PaymentAuthorize
     */
    public static function processWithToken(Cart $cart, string $type, string $paymentToken, array $additionalData = [])
    {
        return static::processPayment($cart, $type, array_merge([
            'payment_token' => $paymentToken,
        ], $additionalData));
    }

    /**
     * Get payment checks from a transaction.
     * 
     * Some providers return information based on checks that occur before payment validation.
     * This is usually related to 3DSecure but can relate to credit checks or other validations.
     * 
     * @param Transaction $transaction
     * @return Collection
     */
    public static function getPaymentChecks(Transaction $transaction): Collection
    {
        return collect($transaction->paymentChecks());
    }

    /**
     * Check if all payment checks were successful.
     * 
     * @param Transaction $transaction
     * @return bool
     */
    public static function allChecksSuccessful(Transaction $transaction): bool
    {
        $checks = static::getPaymentChecks($transaction);
        
        if ($checks->isEmpty()) {
            return true; // No checks means success by default
        }
        
        return $checks->every(fn($check) => $check->successful);
    }

    /**
     * Get the payment type configuration.
     * 
     * @param string $type
     * @return array|null
     */
    public static function getPaymentTypeConfig(string $type): ?array
    {
        return config("lunar.payments.types.{$type}");
    }

    /**
     * Get the status that should be set when payment is released.
     * 
     * @param string $type Payment type
     * @return string|null
     */
    public static function getReleasedStatus(string $type): ?string
    {
        $config = static::getPaymentTypeConfig($type);
        return $config['released'] ?? null;
    }

    /**
     * Check if a payment type exists.
     * 
     * @param string $type
     * @return bool
     */
    public static function paymentTypeExists(string $type): bool
    {
        return !is_null(static::getPaymentTypeConfig($type));
    }

    /**
     * Get all available payment types.
     * 
     * @return array
     */
    public static function getAvailablePaymentTypes(): array
    {
        return array_keys(config('lunar.payments.types', []));
    }
}


