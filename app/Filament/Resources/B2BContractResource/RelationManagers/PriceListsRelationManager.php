<?php

namespace App\Filament\Resources\B2BContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\PriceList;

class PriceListsRelationManager extends RelationManager
{
    protected static string $relationship = 'priceLists';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('parent_id')
                    ->label('Parent Price List')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Inherit prices from parent price list'),

                Forms\Components\TextInput::make('version')
                    ->default('1.0')
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),

                Forms\Components\DatePicker::make('valid_from'),

                Forms\Components\DatePicker::make('valid_to'),

                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(0),
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

                Tables\Columns\TextColumn::make('version')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),

                Tables\Columns\TextColumn::make('prices_count')
                    ->counts('prices')
                    ->label('Prices')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_to')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
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
                Tables\Actions\Action::make('manage_prices')
                    ->label('Manage Prices')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (PriceList $record): string => route('filament.admin.resources.price-lists.edit', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

