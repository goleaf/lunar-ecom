<?php

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Create a new product
     */
    public function create(array $data): Product
    {
        return $this->productService->createProduct($data);
    }

    /**
     * Find product with variants loaded
     */
    public function findWithVariants(int $id): ?Product
    {
        return $this->productService->findWithVariants($id);
    }

    /**
     * Search products by attributes
     */
    public function searchByAttributes(array $filters): Collection
    {
        return $this->productService->searchByAttributes($filters);
    }
}