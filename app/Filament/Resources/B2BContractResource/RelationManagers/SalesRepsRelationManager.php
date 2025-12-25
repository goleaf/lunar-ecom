<?php

namespace App\Filament\Resources\B2BContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;

class SalesRepsRelationManager extends RelationManager
{
    protected static string $relationship = 'salesReps';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Sales Rep')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Sales Rep')
                    ->default(false),

                Forms\Components\TextInput::make('commission_rate')
                    ->label('Commission Rate (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->getStateUsing(fn ($record): string => $record->name ?? 'N/A')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission Rate')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}

