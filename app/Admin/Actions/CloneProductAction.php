<?php

namespace App\Admin\Actions;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Action to clone/duplicate a product.
 */
class CloneProductAction
{
    public static function make(): Action
    {
        return Action::make('clone')
            ->label('Clone Product')
            ->icon('heroicon-o-document-duplicate')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Clone Product')
            ->modalDescription('This will create a copy of the product. Continue?')
            ->action(function (Product $record) {
                try {
                    DB::beginTransaction();
                    
                    // Use product's duplicate method if available
                    if (method_exists($record, 'duplicate')) {
                        $cloned = $record->duplicate();
                    } else {
                        // Manual duplication
                        $cloned = $record->replicate();
                        $cloned->sku = ($cloned->sku ?? '') . '-copy-' . time();
                        $cloned->status = 'draft';
                        $cloned->save();
                        
                        // Duplicate variants
                        foreach ($record->variants as $variant) {
                            $newVariant = $variant->replicate();
                            $newVariant->product_id = $cloned->id;
                            $newVariant->sku = ($newVariant->sku ?? '') . '-copy';
                            $newVariant->save();
                            
                            // Duplicate prices
                            foreach ($variant->prices as $price) {
                                $newPrice = $price->replicate();
                                $newPrice->priceable_id = $newVariant->id;
                                $newPrice->save();
                            }
                        }
                        
                        // Duplicate categories
                        $cloned->categories()->sync($record->categories->pluck('id'));
                        
                        // Duplicate collections
                        $cloned->collections()->sync($record->collections->pluck('id'));
                    }
                    
                    DB::commit();
                    
                    Notification::make()
                        ->title('Product cloned successfully')
                        ->success()
                        ->send();
                    
                    return redirect()->route('filament.admin.resources.products.edit', $cloned);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Notification::make()
                        ->title('Error cloning product')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}

