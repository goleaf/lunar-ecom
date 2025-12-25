<?php

namespace App\Filament\Resources\B2BContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ContractRule;

class RulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('rule_type')
                    ->options([
                        ContractRule::TYPE_PRICE_OVERRIDE => 'Price Override',
                        ContractRule::TYPE_PROMOTION_OVERRIDE => 'Promotion Override',
                        ContractRule::TYPE_PAYMENT_METHOD => 'Payment Method',
                        ContractRule::TYPE_SHIPPING => 'Shipping',
                        ContractRule::TYPE_DISCOUNT => 'Discount',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),

                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(0),

                Forms\Components\KeyValue::make('conditions')
                    ->label('Conditions')
                    ->helperText('Rule conditions (e.g., cart_total, product_categories)')
                    ->columnSpanFull(),

                Forms\Components\KeyValue::make('actions')
                    ->label('Actions')
                    ->helperText('Rule actions (e.g., allowed_methods, shipping_rules)')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rule_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rule_type')
                    ->options([
                        ContractRule::TYPE_PRICE_OVERRIDE => 'Price Override',
                        ContractRule::TYPE_PROMOTION_OVERRIDE => 'Promotion Override',
                        ContractRule::TYPE_PAYMENT_METHOD => 'Payment Method',
                        ContractRule::TYPE_SHIPPING => 'Shipping',
                        ContractRule::TYPE_DISCOUNT => 'Discount',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }
}

