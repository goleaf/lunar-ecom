<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\Models\Contracts\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * Example custom discount type with admin panel integration.
 * 
 * This is a scaffolding example showing how to add form fields for your discount
 * in the Lunar admin panel. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/discounts#adding-form-fields-for-your-discount-in-the-admin-panel
 * 
 * This example extends AbstractDiscountType and implements LunarPanelDiscountInterface
 * to provide admin panel form fields.
 */
class CustomPercentageDiscountWithAdmin extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    /**
     * Return the name of the discount type.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Custom Percentage Discount (with Admin)';
    }

    /**
     * Called just before cart totals are calculated.
     * 
     * Apply the discount logic to the cart.
     *
     * @param Cart $cart
     * @return Cart
     */
    public function apply(Cart $cart): Cart
    {
        // Access discount data from admin form fields
        $percentage = $this->discount->data['percentage'] ?? 10;
        $minPurchase = $this->discount->data['min_purchase'] ?? 0;

        // Apply discount if cart meets minimum purchase requirement
        if ($cart->subTotal->value >= $minPurchase) {
            $discountAmount = (int) ($cart->subTotal->value * ($percentage / 100));
            
            // Apply discount to cart
            // Note: In a real implementation, you would add this to the cart's discount breakdown
            // $cart->discount_total = new \Lunar\DataTypes\Price($discountAmount, $cart->currency, 1);
        }

        return $cart;
    }

    /**
     * Return the schema to use in the Lunar admin panel.
     * 
     * Define form fields that will appear in the discount creation/edit form.
     *
     * @return array
     */
    public function lunarPanelSchema(): array
    {
        return [
            Forms\Components\TextInput::make('data.percentage')
                ->label('Discount Percentage')
                ->numeric()
                ->default(10)
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->helperText('Enter the discount percentage (0-100)'),

            Forms\Components\TextInput::make('data.min_purchase')
                ->label('Minimum Purchase Amount')
                ->numeric()
                ->default(0)
                ->required()
                ->minValue(0)
                ->prefix('$')
                ->helperText('Minimum cart total required to apply this discount'),
        ];
    }

    /**
     * Mutate the model data before displaying it in the admin form.
     * 
     * This allows you to transform the discount's stored data before
     * it's populated into the form fields.
     *
     * @param array $data
     * @return array
     */
    public function lunarPanelOnFill(array $data): array
    {
        // Optionally transform data before displaying in form
        // For example, convert stored values to display format
        // $data['percentage'] = $data['percentage'] ?? 10;
        // $data['min_purchase'] = ($data['min_purchase'] ?? 0) / 100; // Convert from cents to dollars
        
        return $data;
    }

    /**
     * Mutate the form data before saving it to the discount model.
     * 
     * This allows you to transform the form data before it's saved
     * to the discount's data attribute.
     *
     * @param array $data
     * @return array
     */
    public function lunarPanelOnSave(array $data): array
    {
        // Optionally transform data before saving
        // For example, convert dollars to cents for storage
        // if (isset($data['min_purchase'])) {
        //     $data['min_purchase'] = (int) ($data['min_purchase'] * 100);
        // }
        
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


