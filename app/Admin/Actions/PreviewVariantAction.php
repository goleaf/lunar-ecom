<?php

namespace App\Admin\Actions;

use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Preview Variant Action - Preview variant on storefront.
 */
class PreviewVariantAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'preview_variant';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Preview')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(function (ProductVariant $record) {
                return route('storefront.variants.show', [
                    'variant' => $record->id,
                    'preview' => true,
                ]);
            })
            ->openUrlInNewTab();
    }
}


