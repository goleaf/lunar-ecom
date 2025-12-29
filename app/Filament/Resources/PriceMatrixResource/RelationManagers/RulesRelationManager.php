<?php

namespace App\Filament\Resources\PriceMatrixResource\RelationManagers;

use App\Models\PriceMatrixRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RulesRelationManager extends RelationManager
{
    protected static string $relationship = 'pricingRules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('rule_type')
                    ->options([
                        'quantity' => 'Quantity',
                        'customer_group' => 'Customer group',
                        'region' => 'Region',
                        'date' => 'Date',
                        'product' => 'Product',
                        'variant' => 'Variant',
                        'custom' => 'Custom',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('rule_key')
                    ->label('Context key (optional)')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\Select::make('operator')
                    ->options([
                        '=' => '=',
                        '!=' => '!=',
                        '>' => '>',
                        '>=' => '>=',
                        '<' => '<',
                        '<=' => '<=',
                        'in' => 'in',
                        'not_in' => 'not in',
                        'between' => 'between',
                    ])
                    ->default('=')
                    ->required(),

                Forms\Components\Textarea::make('rule_value')
                    ->label('Value')
                    ->rows(2)
                    ->helperText('For "in": comma-separated or JSON array. For "between": JSON array [min,max].')
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Select::make('adjustment_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'override' => 'Override',
                        'add' => 'Add',
                        'subtract' => 'Subtract',
                        'percentage' => 'Percentage',
                    ])
                    ->default('fixed')
                    ->required(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->nullable(),

                Forms\Components\TextInput::make('price_adjustment')
                    ->numeric()
                    ->nullable(),

                Forms\Components\TextInput::make('percentage_discount')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->nullable(),

                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Forms\Components\Textarea::make('conditions')
                    ->label('Extra conditions (JSON)')
                    ->rows(4)
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '')
                    ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null)
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('rule_type')
            ->columns([
                Tables\Columns\TextColumn::make('rule_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rule_key')
                    ->label('Key')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('operator')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('rule_value')
                    ->label('Value')
                    ->limit(40),

                Tables\Columns\TextColumn::make('adjustment_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }
}

