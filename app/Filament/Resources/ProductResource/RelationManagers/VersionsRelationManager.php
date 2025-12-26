<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductVersion;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $recordTitleAttribute = 'version_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->sortable(),
                Tables\Columns\TextColumn::make('version_name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->default('System'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('snapshot')
                    ->label('Create Snapshot')
                    ->icon('heroicon-o-document-text')
                    ->form([
                        Forms\Components\TextInput::make('version_name')
                            ->label('Version Name')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('version_notes')
                            ->label('Version Notes')
                            ->rows(3),
                    ])
                    ->action(function (VersionsRelationManager $livewire, array $data) {
                        $livewire->getOwnerRecord()
                            ->createVersion($data['version_name'] ?? null, $data['version_notes'] ?? null);

                        Notification::make()
                            ->title('Snapshot created')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (VersionsRelationManager $livewire, ProductVersion $record) {
                        $livewire->getOwnerRecord()->restoreVersion($record);
                        Notification::make()
                            ->title('Version restored')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('version_number', 'desc');
    }
}
