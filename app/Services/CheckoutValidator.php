<?php

namespace App\Services;

use App\Models\CheckoutLock;
use Illuminate\Support\Facades\Validator;
use Lunar\Models\Cart;

/**
 * Validation service for checkout operations.
 */
class CheckoutValidator
{
    /**
     * Validate cart can start checkout.
     */
    public function canStartCheckout(Cart $cart): array
    {
        $errors = [];

        // Check cart exists
        if (!$cart) {
            $errors[] = 'Cart not found';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check cart has items
        if ($cart->lines->isEmpty()) {
            $errors[] = 'Cart is empty';
        }

        // Check addresses
        if (!$cart->shippingAddress) {
            $errors[] = 'Shipping address is required';
        }

        if (!$cart->billingAddress) {
            $errors[] = 'Billing address is required';
        }

        // Check if already locked by another session
        $otherLock = CheckoutLock::where('cart_id', $cart->id)
            ->where('session_id', '!=', session()->getId())
            ->active()
            ->first();

        if ($otherLock) {
            $errors[] = 'Cart is currently being checked out by another session';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate lock can be resumed.
     */
    public function canResume(CheckoutLock $lock): array
    {
        $errors = [];

        if (!$lock->canResume()) {
            $errors[] = 'Lock cannot be resumed';
        }

        if ($lock->session_id !== session()->getId()) {
            $errors[] = 'Lock does not belong to current session';
        }

        if ($lock->isExpired()) {
            $errors[] = 'Lock has expired';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate payment data.
     */
    public function validatePaymentData(array $data): array
    {
        $validator = Validator::make($data, [
            'method' => 'required|string|in:card,paypal,bank_transfer',
            'token' => 'required_if:method,card|string',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }
}


