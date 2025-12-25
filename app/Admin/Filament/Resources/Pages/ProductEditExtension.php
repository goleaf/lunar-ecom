<?php

namespace App\Admin\Filament\Resources\Pages;

use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Lunar\Panel\Filament\Resources\ProductResource\Pages\EditProduct;

/**
 * Extended Edit Product page with enhanced UX features.
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
            \App\Admin\Actions\PreviewStorefrontAction::make(),
            \App\Admin\Actions\CloneProductAction::make(),
        ];
    }

    /**
     * Get form schema with enhanced components.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Add real-time validation
        return $data;
    }

    /**
     * Get form tabs with enhanced sections.
     */
    protected function getFormTabs(): array
    {
        return [
            Tabs\Tab::make('Basic')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Section::make('Product Information')
                        ->schema([
                            ...$this->form->getComponents(),
                        ]),
                ]),
            
            Tabs\Tab::make('Variants')
                ->icon('heroicon-o-squares-2x2')
                ->schema([
                    Livewire::make(\App\Admin\Livewire\InlineVariantEditor::class)
                        ->key('variant-editor-' . $this->record->id),
                ]),
            
            Tabs\Tab::make('Media')
                ->icon('heroicon-o-photo')
                ->schema([
                    Livewire::make(\App\Admin\Livewire\DragDropMediaManager::class)
                        ->key('media-manager-' . $this->record->id),
                ]),
            
            Tabs\Tab::make('History')
                ->icon('heroicon-o-clock')
                ->schema([
                    Livewire::make(\App\Admin\Livewire\ChangeHistoryTimeline::class)
                        ->key('history-timeline-' . $this->record->id),
                ]),
        ];
    }
}
