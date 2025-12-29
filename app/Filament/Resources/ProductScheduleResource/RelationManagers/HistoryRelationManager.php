<?php

namespace App\Filament\Resources\ProductScheduleResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    public function form(Form $form): Form
    {
        // History is system-generated.
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('previous_status')
                    ->label('From')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('new_status')
                    ->label('To')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('executed_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('timezone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([])
            ->bulkActions([])
            ->defaultSort('executed_at', 'desc');
    }
}

