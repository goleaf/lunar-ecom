<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\PriceMatrixResource as FilamentPriceMatrixResource;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PriceMatrix;
use App\Models\PricingTier;
use App\Services\MatrixPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PricingImportController extends Controller
{
    public function __construct(
        protected MatrixPricingService $pricingService
    ) {}

    /**
     * Display import interface.
     */
    public function index(Product $product)
    {
        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.' . FilamentPriceMatrixResource::getSlug() . '.index', [
            'product_id' => $product->getKey(),
        ]);
    }

    /**
     * Import pricing from file.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:10240',
            'import_type' => 'required|in:quantity_tiers,customer_group,regional,bulk',
        ]);

        try {
            $data = Excel::toArray([], $request->file('file'))[0];
            
            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                if ($index === 0) {
                    continue; // Skip header
                }

                try {
                    switch ($validated['import_type']) {
                        case 'quantity_tiers':
                            $imported += $this->importQuantityTiers($row);
                            break;
                        case 'customer_group':
                            $imported += $this->importCustomerGroupPricing($row);
                            break;
                        case 'regional':
                            $imported += $this->importRegionalPricing($row);
                            break;
                        case 'bulk':
                            $imported += $this->importBulkPricing($row);
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Imported {$imported} pricing records.",
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import quantity tiers.
     */
    protected function importQuantityTiers(array $row): int
    {
        // Expected format: product_id, variant_id, min_quantity, max_quantity, price, tier_name
        $productId = $row[0] ?? null;
        $variantId = $row[1] ?? null;
        $minQuantity = $row[2] ?? 1;
        $maxQuantity = $row[3] ?? null;
        $price = $row[4] ?? null;
        $tierName = $row[5] ?? null;

        if (!$productId || !$price) {
            throw new \Exception('Missing required fields');
        }

        $product = Product::findOrFail($productId);
        
        $matrix = PriceMatrix::firstOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'matrix_type' => 'quantity',
        ], [
            'is_active' => true,
            'priority' => 0,
        ]);

        PricingTier::create([
            'price_matrix_id' => $matrix->id,
            'tier_name' => $tierName,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
            'price' => $price,
            'pricing_type' => 'fixed',
            'is_active' => true,
        ]);

        return 1;
    }

    /**
     * Import customer group pricing.
     */
    protected function importCustomerGroupPricing(array $row): int
    {
        // Expected format: product_id, variant_id, customer_group, price
        $productId = $row[0] ?? null;
        $variantId = $row[1] ?? null;
        $customerGroup = $row[2] ?? null;
        $price = $row[3] ?? null;

        if (!$productId || !$customerGroup || !$price) {
            throw new \Exception('Missing required fields');
        }

        $product = Product::findOrFail($productId);
        
        $matrix = PriceMatrix::firstOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'matrix_type' => 'customer_group',
        ], [
            'is_active' => true,
            'priority' => 0,
        ]);

        \App\Models\PricingRule::create([
            'price_matrix_id' => $matrix->id,
            'rule_type' => 'customer_group',
            'rule_key' => 'customer_group',
            'operator' => '=',
            'rule_value' => $customerGroup,
            'price' => $price,
            'adjustment_type' => 'fixed',
        ]);

        return 1;
    }

    /**
     * Import regional pricing.
     */
    protected function importRegionalPricing(array $row): int
    {
        // Expected format: product_id, variant_id, region, price
        $productId = $row[0] ?? null;
        $variantId = $row[1] ?? null;
        $region = $row[2] ?? null;
        $price = $row[3] ?? null;

        if (!$productId || !$region || !$price) {
            throw new \Exception('Missing required fields');
        }

        $product = Product::findOrFail($productId);
        
        $matrix = PriceMatrix::firstOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'matrix_type' => 'region',
        ], [
            'is_active' => true,
            'priority' => 0,
        ]);

        \App\Models\PricingRule::create([
            'price_matrix_id' => $matrix->id,
            'rule_type' => 'region',
            'rule_key' => 'region',
            'operator' => '=',
            'rule_value' => $region,
            'price' => $price,
            'adjustment_type' => 'fixed',
        ]);

        return 1;
    }

    /**
     * Import bulk pricing.
     */
    protected function importBulkPricing(array $row): int
    {
        // More complex format - can handle multiple fields
        // This is a simplified version
        return $this->importQuantityTiers($row);
    }
}


