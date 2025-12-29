<?php

namespace App\Filament\Resources\ProductImportResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    protected static ?string $title = 'Rows';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('row_number')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('sku')->toggleable(),
                Tables\Columns\TextColumn::make('product_id')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('error_message')->limit(60)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('success_message')->limit(60)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'success' => 'Success',
                    'failed' => 'Failed',
                    'skipped' => 'Skipped',
                ]),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}

