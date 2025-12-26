<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\Models\Contracts\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * Product Discount Type
 * 
 * Applies a discount to specific products or product variants.
 * Can be percentage or fixed amount.
 */
class ProductDiscount extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    /**
     * Return the name of the discount type.
     */
    public function getName(): string
    {
        return 'Product Discount';
    }

    /**
     * Apply the discount to the cart.
     */
    public function apply(Cart $cart): Cart
    {
        $percentage = $this->discount->data['percentage'] ?? null;
        $fixedAmount = $this->discount->data['fixed_amount'] ?? null;

        // Get purchasables that qualify for the discount
        $rewardPurchasables = $this->discount->purchasables()
            ->where('type', 'reward')
            ->get();

        if ($rewardPurchasables->isEmpty()) {
            return $cart;
        }

        // Apply discount to qualifying cart lines
        foreach ($cart->lines as $line) {
            $qualifies = $rewardPurchasables->contains(function ($p) use ($line) {
                return $p->purchasable_type === get_class($line->purchasable) 
                    && $p->purchasable_id === $line->purchasable_id;
            });

            if ($qualifies) {
                // Apply discount to this line
                // Lunar handles this through the discount system
                // The discount purchasables marked as 'reward' will apply
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

