<?php

namespace App\Admin\Filament\Resources\Pages;

use Filament\Actions;
use Lunar\Panel\Filament\Resources\ProductResource\Pages\EditProduct;

/**
 * Example extension for the Edit Product page.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/overview#extending-edit-resource
 * 
 * Extensions allow you to add custom functionality to Lunar's admin panel pages,
 * such as custom actions, buttons, or modified behavior.
 */
class ProductEditExtension extends EditProduct
{
    /**
     * Get header actions.
     * 
     * You can override this method to add custom actions to the page header.
     */
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            // Add your custom actions here
            // Actions\Action::make('customAction')
            //     ->label('Custom Action')
            //     ->action(function () {
            //         // Your custom action logic
            //     }),
        ];
    }

    /**
     * Get the form actions.
     * 
     * You can override this method to customize form actions.
     */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            // Add custom form actions if needed
        ];
    }

    // Add any other methods you need to extend the EditProduct page behavior
}


