<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductVariantService;
use App\Services\ProductOptionService;
use Illuminate\Console\Command;
use Lunar\Models\Currency;

class ManageProductVariants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:variants {action} {--product=} {--sku=} {--stock=} {--threshold=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage product variants - create, update stock, check availability';

    public function __construct(
        protected ProductVariantService $variantService,
        protected ProductOptionService $optionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        match ($action) {
            'create' => $this->createVariant(),
            'update-stock' => $this->updateStock(),
            'check-availability' => $this->checkAvailability(),
            'low-stock' => $this->showLowStock(),
            'generate-sku' => $this->generateSku(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    protected function createVariant(): void
    {
        $productId = $this->option('product');
        if (!$productId) {
            $this->error('Product ID is required. Use --product=ID');
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product with ID {$productId} not found");
            return;
        }

        $sku = $this->ask('Enter SKU for the variant');
        $stock = (int) $this->ask('Enter initial stock quantity', '0');
        
        $variantData = [
            'sku' => $sku,
            'stock' => $stock,
            'purchasable' => 'always',
            'shippable' => true,
        ];

        // Add basic pricing
        $currency = Currency::where('default', true)->first();
        if ($currency) {
            $price = $this->ask('Enter price for the variant', '0');
            $variantData['prices'] = [
                $currency->code => [
                    'price' => (float) $price,
                    'min_quantity' => 1,
                ]
            ];
        }

        try {
            $variant = $this->variantService->createVariant($product, $variantData);
            $this->info("Variant created successfully with ID: {$variant->id}");
        } catch (\Exception $e) {
            $this->error("Failed to create variant: {$e->getMessage()}");
        }
    }

    protected function updateStock(): void
    {
        $sku = $this->option('sku');
        $stock = $this->option('stock');

        if (!$sku || $stock === null) {
            $this->error('Both --sku and --stock options are required');
            return;
        }

        $variant = \App\Models\ProductVariant::where('sku', $sku)->first();
        if (!$variant) {
            $this->error("Variant with SKU {$sku} not found");
            return;
        }

        try {
            $updatedVariant = $this->variantService->updateStock($variant, (int) $stock);
            $this->info("Stock updated for SKU {$sku}: {$updatedVariant->stock}");
        } catch (\Exception $e) {
            $this->error("Failed to update stock: {$e->getMessage()}");
        }
    }

    protected function checkAvailability(): void
    {
        $sku = $this->option('sku');
        if (!$sku) {
            $this->error('SKU is required. Use --sku=VALUE');
            return;
        }

        $variant = \App\Models\ProductVariant::where('sku', $sku)->first();
        if (!$variant) {
            $this->error("Variant with SKU {$sku} not found");
            return;
        }

        $isAvailable = $this->variantService->isAvailable($variant);
        $status = $isAvailable ? 'Available' : 'Not Available';
        
        $this->info("Variant {$sku}: {$status}");
        $this->info("Stock: {$variant->stock}");
        $this->info("Purchasable: {$variant->purchasable}");
    }

    protected function showLowStock(): void
    {
        $threshold = (int) $this->option('threshold');
        $lowStockVariants = $this->variantService->getLowStockVariants($threshold);

        if ($lowStockVariants->isEmpty()) {
            $this->info("No variants with stock below {$threshold}");
            return;
        }

        $this->info("Variants with stock below {$threshold}:");
        $this->table(
            ['SKU', 'Product', 'Stock', 'Purchasable'],
            $lowStockVariants->map(function ($variant) {
                return [
                    $variant->sku,
                    $variant->product->productType->name ?? 'N/A',
                    $variant->stock,
                    $variant->purchasable,
                ];
            })
        );
    }

    protected function generateSku(): void
    {
        $productId = $this->option('product');
        if (!$productId) {
            $this->error('Product ID is required. Use --product=ID');
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product with ID {$productId} not found");
            return;
        }

        $sku = $this->variantService->generateSku($product);
        $this->info("Generated SKU: {$sku}");
    }
}
