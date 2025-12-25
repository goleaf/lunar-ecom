<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for checkout-related errors.
 */
class CheckoutException extends Exception
{
    /**
     * Create a new checkout exception.
     */
    public function __construct(
        string $message,
        public readonly ?string $phase = null,
        public readonly ?array $context = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Checkout failed',
                'message' => $this->getMessage(),
                'phase' => $this->phase,
                'context' => $this->context,
            ], 422);
        }

        return redirect()->back()
            ->with('error', $this->getMessage());
    }
}

