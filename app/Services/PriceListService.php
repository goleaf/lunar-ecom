<?php

namespace App\Services;

use App\Models\B2BContract;
use App\Models\PriceList;
use App\Models\ContractPrice;
use Lunar\Models\Customer;
use Lunar\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Price List Service
 * 
 * Handles price list management, price calculation, and inheritance logic.
 */
class PriceListService
{
    /**
     * Get active price lists for a contract.
     * 
     * @param B2BContract $contract
     * @return Collection
     */
    public function getActivePriceLists(B2BContract $contract): Collection
    {
        return $contract->activePriceLists()
            ->byPriority()
            ->get();
    }

    /**
     * Get price for a variant from contract price lists.
     * 
     * @param ProductVariant $variant
     * @param Customer $customer
     * @param int $quantity
     * @param int|null $basePrice Base price in minor currency units (optional)
     * @return array|null Returns ['price' => int, 'price_list_id' => int, 'contract_id' => int] or null
     */
    public function getContractPrice(
        ProductVariant $variant,
        Customer $customer,
        int $quantity = 1,
        ?int $basePrice = null
    ): ?array {
        $contractService = app(ContractService::class);
        $contracts = $contractService->getActiveContractsForCustomer($customer);

        if ($contracts->isEmpty()) {
            return null;
        }

        // Try each contract in priority order
        foreach ($contracts as $contract) {
            $priceLists = $this->getActivePriceLists($contract);

            foreach ($priceLists as $priceList) {
                $price = $this->findPriceInPriceList($priceList, $variant, $quantity, $basePrice);

                if ($price !== null) {
                    return [
                        'price' => $price,
                        'price_list_id' => $priceList->id,
                        'contract_id' => $contract->id,
                        'version' => $priceList->version,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Find price for a variant in a specific price list.
     * 
     * @param PriceList $priceList
     * @param ProductVariant $variant
     * @param int $quantity
     * @param int|null $basePrice
     * @return int|null
     */
    protected function findPriceInPriceList(
        PriceList $priceList,
        ProductVariant $variant,
        int $quantity,
        ?int $basePrice
    ): ?int {
        // Get prices from this price list and inherited prices
        $prices = $this->getPricesForPriceList($priceList);

        // Try variant-specific prices first (most specific)
        $variantPrices = $prices->where('pricing_type', ContractPrice::TYPE_VARIANT_FIXED)
            ->where('product_variant_id', $variant->id)
            ->matchingQuantity($quantity)
            ->sortByDesc('quantity_break'); // Highest quantity break first

        foreach ($variantPrices as $contractPrice) {
            $price = $contractPrice->calculatePrice($variant, $quantity, $basePrice);
            if ($price !== null) {
                return $price;
            }
        }

        // Try category-based prices
        if ($variant->product) {
            $productCategories = $variant->product->collections()->pluck('id')->toArray();

            $categoryPrices = $prices->where('pricing_type', ContractPrice::TYPE_CATEGORY)
                ->whereIn('category_id', $productCategories)
                ->matchingQuantity($quantity)
                ->sortByDesc('quantity_break');

            foreach ($categoryPrices as $contractPrice) {
                $price = $contractPrice->calculatePrice($variant, $quantity, $basePrice);
                if ($price !== null) {
                    return $price;
                }
            }
        }

        // Try margin-based prices
        $marginPrices = $prices->where('pricing_type', ContractPrice::TYPE_MARGIN_BASED)
            ->matchingQuantity($quantity)
            ->sortByDesc('priority');

        foreach ($marginPrices as $contractPrice) {
            $price = $contractPrice->calculatePrice($variant, $quantity, $basePrice);
            if ($price !== null) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Get all prices for a price list (including inherited).
     * 
     * @param PriceList $priceList
     * @return Collection
     */
    protected function getPricesForPriceList(PriceList $priceList): Collection
    {
        // Get prices from this price list
        $prices = $priceList->prices;

        // If price list has a parent, merge inherited prices
        if ($priceList->parent_id) {
            $inheritedPrices = $this->getPricesForPriceList($priceList->parent);
            
            // Merge: this price list's prices override parent prices
            $merged = collect();
            
            foreach ($inheritedPrices as $inheritedPrice) {
                // Check if there's an override in this price list
                $override = $prices->first(function ($price) use ($inheritedPrice) {
                    return $price->pricing_type === $inheritedPrice->pricing_type
                        && $price->product_variant_id === $inheritedPrice->product_variant_id
                        && $price->category_id === $inheritedPrice->category_id
                        && $price->quantity_break === $inheritedPrice->quantity_break;
                });

                if (!$override) {
                    $merged->push($inheritedPrice);
                }
            }

            // Add this price list's prices (overrides)
            $merged = $merged->merge($prices);
            
            return $merged;
        }

        return $prices;
    }

    /**
     * Create a new price list.
     * 
     * @param B2BContract $contract
     * @param array $data
     * @return PriceList
     */
    public function createPriceList(B2BContract $contract, array $data): PriceList
    {
        $data['contract_id'] = $contract->id;
        
        // Set default version if not provided
        if (empty($data['version'])) {
            $data['version'] = '1.0';
        }

        return PriceList::create($data);
    }

    /**
     * Add a price to a price list.
     * 
     * @param PriceList $priceList
     * @param array $data
     * @return ContractPrice
     */
    public function addPrice(PriceList $priceList, array $data): ContractPrice
    {
        $data['price_list_id'] = $priceList->id;
        return ContractPrice::create($data);
    }

    /**
     * Get quantity breaks for a variant in price lists.
     * 
     * @param ProductVariant $variant
     * @param Customer $customer
     * @return Collection Collection of quantity breaks with prices
     */
    public function getQuantityBreaks(
        ProductVariant $variant,
        Customer $customer
    ): Collection {
        $contractService = app(ContractService::class);
        $contracts = $contractService->getActiveContractsForCustomer($customer);

        $breaks = collect();

        foreach ($contracts as $contract) {
            $priceLists = $this->getActivePriceLists($contract);

            foreach ($priceLists as $priceList) {
                $prices = $this->getPricesForPriceList($priceList);

                $variantPrices = $prices->where('pricing_type', ContractPrice::TYPE_VARIANT_FIXED)
                    ->where('product_variant_id', $variant->id)
                    ->whereNotNull('quantity_break')
                    ->sortBy('quantity_break');

                foreach ($variantPrices as $price) {
                    $calculatedPrice = $price->calculatePrice($variant, $price->quantity_break);
                    
                    if ($calculatedPrice !== null) {
                        $breaks->push([
                            'quantity' => $price->quantity_break,
                            'price' => $calculatedPrice,
                            'price_list_id' => $priceList->id,
                            'contract_id' => $contract->id,
                        ]);
                    }
                }
            }
        }

        return $breaks->unique('quantity')->sortBy('quantity')->values();
    }
}


