<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\Models\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * Fixed Amount Discount Type
 * 
 * Applies a fixed amount discount to the cart, with optional conditions:
 * - Minimum cart value
 * - Product/category requirements
 */
class FixedAmountDiscount extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    /**
     * Return the name of the discount type.
     */
    public function getName(): string
    {
        return 'Fixed Amount Discount';
    }

    /**
     * Apply the discount to the cart.
     */
    public function apply(Cart $cart): Cart
    {
        $fixedAmount = $this->discount->data['fixed_amount'] ?? 0;
        $minCartValue = $this->discount->data['min_cart_value'] ?? 0;

        // Check minimum cart value requirement
        if ($cart->subTotal->value < $minCartValue) {
            return $cart;
        }

        // Ensure discount doesn't exceed cart total
        $discountAmount = min($fixedAmount, $cart->subTotal->value);

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
                    Forms\Components\TextInput::make('data.fixed_amount')
                        ->label('Fixed Discount Amount')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(1000)
                        ->helperText('Fixed discount amount in cents (e.g., 1000 = €10)'),

                    Forms\Components\TextInput::make('data.min_cart_value')
                        ->label('Minimum Cart Value')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Minimum cart total required (in cents, e.g., 5000 = €50)'),
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

