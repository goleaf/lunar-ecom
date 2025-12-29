<?php

namespace App\Filament\Resources\PriceMatrixResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TiersRelationManager extends RelationManager
{
    protected static string $relationship = 'allTiers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tier_name')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('min_quantity')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                Forms\Components\TextInput::make('max_quantity')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\Select::make('pricing_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'adjustment' => 'Adjustment',
                        'percentage' => 'Percentage',
                    ])
                    ->default('fixed')
                    ->required(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Used when pricing type is fixed.'),

                Forms\Components\TextInput::make('price_adjustment')
                    ->numeric()
                    ->nullable()
                    ->helperText('Used when pricing type is adjustment.'),

                Forms\Components\TextInput::make('percentage_discount')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->nullable()
                    ->helperText('Used when pricing type is percentage.'),

                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tier_name')
            ->columns([
                Tables\Columns\TextColumn::make('tier_name')
                    ->label('Tier')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('min_quantity')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_quantity')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pricing_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('min_quantity');
    }
}

