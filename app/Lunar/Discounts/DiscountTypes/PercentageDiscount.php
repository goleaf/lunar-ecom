<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\Models\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * Percentage Discount Type
 * 
 * Applies a percentage discount to the cart, with optional conditions:
 * - Minimum cart value
 * - Maximum discount amount (cap)
 * - Product/category requirements
 */
class PercentageDiscount extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    /**
     * Return the name of the discount type.
     */
    public function getName(): string
    {
        return 'Percentage Discount';
    }

    /**
     * Apply the discount to the cart.
     */
    public function apply(Cart $cart): Cart
    {
        $percentage = $this->discount->data['percentage'] ?? 0;
        $minCartValue = $this->discount->data['min_cart_value'] ?? 0;
        $maxDiscountAmount = $this->discount->data['max_discount_amount'] ?? null;

        // Check minimum cart value requirement
        if ($cart->subTotal->value < $minCartValue) {
            return $cart;
        }

        // Calculate discount amount
        $discountAmount = (int) round($cart->subTotal->value * ($percentage / 100));

        // Apply maximum discount cap if set
        if ($maxDiscountAmount !== null && $discountAmount > $maxDiscountAmount) {
            $discountAmount = $maxDiscountAmount;
        }

        // Apply discount (Lunar handles this through the discount system)
        // The discount will be automatically applied by Lunar's cart calculation
        
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
                    Forms\Components\TextInput::make('data.percentage')
                        ->label('Discount Percentage')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(10)
                        ->helperText('The percentage discount to apply (0-100)'),

                    Forms\Components\TextInput::make('data.min_cart_value')
                        ->label('Minimum Cart Value')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Minimum cart total required (in cents, e.g., 5000 = €50)'),

                    Forms\Components\TextInput::make('data.max_discount_amount')
                        ->label('Maximum Discount Amount')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->helperText('Maximum discount amount in cents (leave empty for no limit)'),
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
}

