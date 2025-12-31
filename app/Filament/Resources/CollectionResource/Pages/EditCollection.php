<?php

namespace App\Filament\Resources\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use App\Models\Collection;
use App\Services\CollectionManagementService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process_auto_assignment')
                ->label('Process auto assignment')
                ->icon('heroicon-o-bolt')
                ->requiresConfirmation()
                ->visible(fn (): bool => (bool) ($this->record?->auto_assign))
                ->action(function (): void {
                    $record = $this->record;

                    if (! $record instanceof Collection) {
                        return;
                    }

                    $count = app(CollectionManagementService::class)->processAutoAssignment($record);

                    Notification::make()
                        ->title("Auto assignment processed ({$count} products assigned)")
                        ->success()
                        ->send();
                }),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

