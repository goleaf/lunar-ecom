<?php

namespace App\Filament\Resources\CollectionResource\RelationManagers;

use App\Models\CollectionProductMetadata;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductMetadataRelationManager extends RelationManager
{
    protected static string $relationship = 'productMetadata';

    protected static ?string $recordTitleAttribute = 'product_id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => (string) (
                        $record->translateAttribute('name') ?? "Product #{$record->id}"
                    ))
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Toggle::make('is_auto_assigned')
                    ->label('Auto assigned')
                    ->default(false),

                Forms\Components\TextInput::make('position')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                Forms\Components\DateTimePicker::make('assigned_at')
                    ->default(now())
                    ->required(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->nullable(),

                Forms\Components\Textarea::make('metadata')
                    ->label('Metadata (JSON)')
                    ->rows(3)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                    })
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (CollectionProductMetadata $record): string => (string) (
                        $record->product?->translateAttribute('name')
                            ?? "Product #{$record->product_id}"
                    ))
                    ->limit(60),

                Tables\Columns\IconColumn::make('is_auto_assigned')
                    ->label('Auto')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('position')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
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
            ->reorderable('position')
            ->defaultSort('position');
    }
}

