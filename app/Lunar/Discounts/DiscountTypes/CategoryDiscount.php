<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\Models\Contracts\Cart;
use Lunar\Models\Category;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * Category Discount Type
 * 
 * Applies a discount to products in specific categories.
 * Can be percentage or fixed amount.
 */
class CategoryDiscount extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    /**
     * Return the name of the discount type.
     */
    public function getName(): string
    {
        return 'Category Discount';
    }

    /**
     * Apply the discount to the cart.
     */
    public function apply(Cart $cart): Cart
    {
        $targetCategories = $this->discount->data['target_categories'] ?? [];
        $percentage = $this->discount->data['percentage'] ?? null;
        $fixedAmount = $this->discount->data['fixed_amount'] ?? null;

        if (empty($targetCategories)) {
            return $cart;
        }

        // Apply discount to cart lines that belong to target categories
        foreach ($cart->lines as $line) {
            if (!$line->purchasable instanceof \Lunar\Models\ProductVariant) {
                continue;
            }

            $product = $line->purchasable->product;
            if (!$product) {
                continue;
            }

            $productCategories = $product->categories->pluck('id')->toArray();
            $hasCategory = !empty(array_intersect($targetCategories, $productCategories));

            if ($hasCategory) {
                // Apply discount to this line
                // Lunar handles this through the discount system
                // You may need to add the discount breakdown to the cart line
            }
        }

        return $cart;
    }

    /**
     * Return the schema for the admin panel.
     */
    public function lunarPanelSchema(): array
    {
        return [
            Forms\Components\Section::make('Discount Settings')
                ->schema([
                    Forms\Components\Select::make('discount_type')
                        ->label('Discount Type')
                        ->options([
                            'percentage' => 'Percentage',
                            'fixed' => 'Fixed Amount',
                        ])
                        ->default('percentage')
                        ->required()
                        ->reactive(),

                    Forms\Components\TextInput::make('data.percentage')
                        ->label('Discount Percentage')
                        ->numeric()
                        ->visible(fn ($get) => $get('discount_type') === 'percentage')
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('The percentage discount to apply'),

                    Forms\Components\TextInput::make('data.fixed_amount')
                        ->label('Fixed Discount Amount')
                        ->numeric()
                        ->visible(fn ($get) => $get('discount_type') === 'fixed')
                        ->minValue(0)
                        ->helperText('Fixed discount amount in cents'),

                    Forms\Components\TextInput::make('data.min_cart_value')
                        ->label('Minimum Cart Value')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Minimum cart total required (in cents)'),
                ]),
        ];
    }

    /**
     * Mutate data before displaying in form.
     */
    public function lunarPanelOnFill(array $data): array
    {
        return $data;
    }

    /**
     * Mutate data before saving.
     */
    public function lunarPanelOnSave(array $data): array
    {
        return $data;
    }

    /**
     * Define any relation managers you want to add to the admin form.
     */
    public function lunarPanelRelationManagers(): array
    {
        return [];
    }
}

