<?php

namespace App\Admin\Actions;

use App\Models\ProductVariant;
use App\Services\VariantLifecycleService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Clone Variant Action - Clone a variant.
 */
class CloneVariantAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'clone_variant';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Clone')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Clone Variant')
            ->modalDescription('Are you sure you want to clone this variant?')
            ->action(function (ProductVariant $record) {
                try {
                    $service = app(VariantLifecycleService::class);
                    $cloned = $service->clone($record, [
                        'sku' => $record->sku . '-CLONE-' . \Illuminate\Support\Str::random(4),
                    ]);

                    Notification::make()
                        ->title('Variant Cloned')
                        ->success()
                        ->body("Variant cloned successfully. New SKU: {$cloned->sku}")
                        ->send();

                    $this->redirect(route('filament.admin.resources.products.edit', [
                        'record' => $record->product_id,
                        'tab' => 'variants',
                    ]));
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}


