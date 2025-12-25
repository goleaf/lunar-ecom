<?php

namespace App\Contracts;

use App\Models\Product;

/**
 * Interface for product hooks.
 * 
 * Hooks allow plugins to intercept product operations:
 * - Before/after create
 * - Before/after update
 * - Before/after delete
 * - Before/after publish
 * - Custom events
 */
interface ProductHookInterface
{
    /**
     * Get hook identifier.
     *
     * @return string
     */
    public function getHookIdentifier(): string;

    /**
     * Get hook name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get supported events.
     *
     * @return array
     */
    public function getSupportedEvents(): array;

    /**
     * Handle hook event.
     *
     * @param  string  $event
     * @param  Product  $product
     * @param  array  $data
     * @return mixed
     */
    public function handle(string $event, Product $product, array $data = []);
}

