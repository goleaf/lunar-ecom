<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductCoreService;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn (Product $record) => route('frontend.products.show', $record->defaultUrl?->slug ?? $record->id))
                ->openUrlInNewTab(),
            Actions\Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (Product $record) => !$record->isPublished())
                ->requiresConfirmation()
                ->action(function (Product $record) {
                    $record->publish();
                    Notification::make()
                        ->title('Product published')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (Product $record) => $record->isPublished())
                ->requiresConfirmation()
                ->action(function (Product $record) {
                    $record->unpublish();
                    Notification::make()
                        ->title('Product unpublished')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('schedulePublish')
                ->label('Schedule Publish')
                ->icon('heroicon-o-clock')
                ->form([
                    DateTimePicker::make('publish_at')
                        ->label('Publish At')
                        ->required(),
                ])
                ->action(function (Product $record, array $data) {
                    app(ProductCoreService::class)->publishProduct($record, $data['publish_at']);
                    Notification::make()
                        ->title('Publish scheduled')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('scheduleUnpublish')
                ->label('Schedule Unpublish')
                ->icon('heroicon-o-clock')
                ->form([
                    DateTimePicker::make('unpublish_at')
                        ->label('Unpublish At')
                        ->required(),
                ])
                ->action(function (Product $record, array $data) {
                    app(ProductCoreService::class)->unpublishProduct($record, $data['unpublish_at']);
                    Notification::make()
                        ->title('Unpublish scheduled')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('lock')
                ->label('Lock')
                ->icon('heroicon-o-lock-closed')
                ->visible(fn (Product $record) => !$record->isLocked())
                ->form([
                    Textarea::make('reason')
                        ->label('Lock Reason')
                        ->rows(3),
                ])
                ->action(function (Product $record, array $data) {
                    app(ProductCoreService::class)->lockProduct($record, $data['reason'] ?? null);
                    Notification::make()
                        ->title('Product locked')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('unlock')
                ->label('Unlock')
                ->icon('heroicon-o-lock-open')
                ->visible(fn (Product $record) => $record->isLocked())
                ->requiresConfirmation()
                ->action(function (Product $record) {
                    app(ProductCoreService::class)->unlockProduct($record);
                    Notification::make()
                        ->title('Product unlocked')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('snapshot')
                ->label('Create Version')
                ->icon('heroicon-o-document-text')
                ->form([
                    Textarea::make('version_name')
                        ->label('Version Name')
                        ->rows(1),
                    Textarea::make('version_notes')
                        ->label('Version Notes')
                        ->rows(3),
                ])
                ->action(function (Product $record, array $data) {
                    $record->createVersion($data['version_name'] ?? null, $data['version_notes'] ?? null);
                    Notification::make()
                        ->title('Version snapshot created')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('clone')
                ->label('Clone')
                ->icon('heroicon-o-document-duplicate')
                ->requiresConfirmation()
                ->action(function (Product $record) {
                    $cloned = app(ProductCoreService::class)->duplicateProduct($record);
                    Notification::make()
                        ->title('Product cloned')
                        ->success()
                        ->send();

                    return redirect(ProductResource::getUrl('edit', ['record' => $cloned]));
                }),
        ];
    }
}

