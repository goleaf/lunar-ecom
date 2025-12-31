<?php

namespace App\Filament\Resources\CollectionResource\RelationManagers;

use App\Models\SmartCollectionRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SmartRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'smartRules';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        $fieldOptions = collect(SmartCollectionRule::getAvailableFields())
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label'] ?? $key])
            ->all();

        $operatorOptions = SmartCollectionRule::getAvailableOperators();

        return $form
            ->schema([
                Forms\Components\Select::make('field')
                    ->options($fieldOptions)
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('operator')
                    ->options($operatorOptions)
                    ->required(),

                Forms\Components\Textarea::make('value')
                    ->label('Value (JSON or scalar)')
                    ->rows(3)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state;
                        }

                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                    })
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('logic_group')
                    ->maxLength(100)
                    ->nullable(),

                Forms\Components\Select::make('group_operator')
                    ->options([
                        'and' => 'AND',
                        'or' => 'OR',
                    ])
                    ->default('and')
                    ->required(),

                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),

                Forms\Components\Textarea::make('description')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        $fieldOptions = collect(SmartCollectionRule::getAvailableFields())
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label'] ?? $key])
            ->all();

        $operatorOptions = SmartCollectionRule::getAvailableOperators();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('field')
                    ->formatStateUsing(fn (?string $state) => $fieldOptions[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('operator')
                    ->formatStateUsing(fn (?string $state) => $operatorOptions[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority');
    }
}

