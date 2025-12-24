<?php

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Create a new product
     */
    public function create(array $data): Product;

    /**
     * Find product with variants loaded
     */
    public function findWithVariants(int $id): ?Product;

    /**
     * Search products by attributes
     */
    public function searchByAttributes(array $filters): Collection;
}