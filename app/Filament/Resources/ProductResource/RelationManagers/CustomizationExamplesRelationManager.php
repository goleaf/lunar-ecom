<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\CustomizationExample;
use App\Models\ProductCustomization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CustomizationExamplesRelationManager extends RelationManager
{
    protected static string $relationship = 'customizationExamples';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customization_id')
                    ->label('Customization')
                    ->options(fn (): array => ProductCustomization::query()
                        ->where('product_id', $this->getOwnerRecord()->getKey())
                        ->orderBy('display_order')
                        ->pluck('field_label', 'id')
                        ->all())
                    ->searchable()
                    ->nullable(),

                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('example_image')
                    ->label('Example image')
                    ->disk('public')
                    ->directory('customizations/examples')
                    ->image()
                    ->maxSize(2048)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('customization_values')
                    ->label('Customization values (JSON)')
                    ->rows(3)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    })
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('customization_label')
                    ->label('Customization')
                    ->getStateUsing(fn (CustomizationExample $record): ?string => $record->customization?->field_label)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
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
            ->reorderable('display_order')
            ->defaultSort('display_order');
    }
}

