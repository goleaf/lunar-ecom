<?php

namespace App\Admin\Actions;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Action to preview product on storefront.
 */
class PreviewStorefrontAction
{
    public static function make(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn (Product $record) => route('storefront.products.show', [
                'product' => $record->slug ?? $record->id,
                'preview' => true,
            ]))
            ->openUrlInNewTab();
    }
}

