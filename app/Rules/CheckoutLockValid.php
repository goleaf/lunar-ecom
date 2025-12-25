<?php

namespace App\Rules;

use App\Models\CheckoutLock;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule to ensure checkout lock is valid and active.
 */
class CheckoutLockValid implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            $fail('Checkout lock ID is required.');
            return;
        }

        $lock = CheckoutLock::find($value);

        if (!$lock) {
            $fail('Checkout lock not found.');
            return;
        }

        if (!$lock->isActive()) {
            $fail('Checkout lock is not active.');
            return;
        }

        // Verify lock belongs to current session
        if ($lock->session_id !== session()->getId()) {
            $fail('Checkout lock does not belong to current session.');
            return;
        }
    }
}

