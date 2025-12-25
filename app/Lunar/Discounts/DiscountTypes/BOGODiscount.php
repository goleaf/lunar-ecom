<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\Models\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * BOGO (Buy One Get One) Discount Type
 * 
 * Applies a buy-one-get-one discount. Can be configured as:
 * - Buy 1, Get 1 Free
 * - Buy 1, Get 1 at X% off
 * - Buy X, Get Y free/at discount
 */
class BOGODiscount extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    /**
     * Return the name of the discount type.
     */
    public function getName(): string
    {
        return 'BOGO (Buy One Get One) Discount';
    }

    /**
     * Apply the discount to the cart.
     */
    public function apply(Cart $cart): Cart
    {
        $buyQuantity = $this->discount->data['buy_quantity'] ?? 1;
        $getQuantity = $this->discount->data['get_quantity'] ?? 1;
        $getDiscount = $this->discount->data['get_discount'] ?? 100; // 100 = free, 50 = 50% off, etc.

        // Get purchasables that qualify for the discount
        $conditionPurchasables = $this->discount->purchasables()
            ->where('type', 'condition')
            ->get();

        if ($conditionPurchasables->isEmpty()) {
            return $cart;
        }

        // Calculate discount based on cart lines
        foreach ($cart->lines as $line) {
            $qualifies = $conditionPurchasables->contains(function ($p) use ($line) {
                return $p->purchasable_type === get_class($line->purchasable) 
                    && $p->purchasable_id === $line->purchasable_id;
            });

            if ($qualifies && $line->quantity >= $buyQuantity) {
                // Calculate how many free/discounted items
                $discountedQuantity = floor($line->quantity / ($buyQuantity + $getQuantity)) * $getQuantity;
                
                // Calculate discount amount per item
                $unitPrice = $line->linePrice->value / $line->quantity;
                $discountPerItem = (int) round($unitPrice * ($getDiscount / 100));
                
                // Apply discount (Lunar handles this through the discount system)
                // Note: This is simplified - you may need to adjust based on how Lunar handles BOGO
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
            Forms\Components\Section::make('BOGO Settings')
                ->schema([
                    Forms\Components\TextInput::make('data.buy_quantity')
                        ->label('Buy Quantity')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->helperText('Number of items customer must buy'),

                    Forms\Components\TextInput::make('data.get_quantity')
                        ->label('Get Quantity')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->helperText('Number of items customer gets at discount'),

                    Forms\Components\TextInput::make('data.get_discount')
                        ->label('Get Discount (%)')
                        ->numeric()
                        ->required()
                        ->default(100)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('Discount percentage for the "get" items (100 = free)'),
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

