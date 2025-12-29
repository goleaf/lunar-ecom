<?php

namespace App\Filament\Resources\ProductImportResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RollbacksRelationManager extends RelationManager
{
    protected static string $relationship = 'rollbacks';

    protected static ?string $title = 'Rollbacks';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_id')->sortable(),
                Tables\Columns\TextColumn::make('action')->badge()->sortable(),
                Tables\Columns\TextColumn::make('rolled_back_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('rolled_back_by')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}

