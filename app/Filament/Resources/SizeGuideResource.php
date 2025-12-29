<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SizeGuideResource\Pages;
use App\Models\SizeGuide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Lunar\Models\Product;

class SizeGuideResource extends Resource
{
    protected static ?string $model = SizeGuide::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category_type')
                            ->options([
                                'clothing' => 'Clothing',
                                'shoes' => 'Shoes',
                                'accessories' => 'Accessories',
                                'jewelry' => 'Jewelry',
                                'bags' => 'Bags',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Select::make('gender')
                            ->options([
                                'men' => 'Men',
                                'women' => 'Women',
                                'unisex' => 'Unisex',
                                'kids' => 'Kids',
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('measurement_unit')
                            ->options([
                                'cm' => 'Centimeters',
                                'inches' => 'Inches',
                                'both' => 'Both',
                            ])
                            ->default('cm')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Size Chart Data')
                    ->description('Define the size measurements for this guide. Use JSON format with sizes array.')
                    ->schema([
                        Forms\Components\Textarea::make('size_chart_data')
                            ->label('Size Chart (JSON)')
                            ->helperText('Enter size chart data in JSON format. Example: {"sizes": [{"size": "S", "chest": "86-91", "waist": "71-76", "hips": "91-96"}, {"size": "M", "chest": "91-96", "waist": "76-81", "hips": "96-101"}]}')
                            ->rows(10)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Associated Products')
                    ->schema([
                        Forms\Components\Select::make('products')
                            ->relationship('products', 'id')
                            ->getOptionLabelFromRecordUsing(
                                fn (Product $record): string => (string) ($record->translate('name') ?? "Product #{$record->id}")
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clothing' => 'success',
                        'shoes' => 'info',
                        'accessories' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_type')
                    ->options([
                        'clothing' => 'Clothing',
                        'shoes' => 'Shoes',
                        'accessories' => 'Accessories',
                        'jewelry' => 'Jewelry',
                        'bags' => 'Bags',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'men' => 'Men',
                        'women' => 'Women',
                        'unisex' => 'Unisex',
                        'kids' => 'Kids',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSizeGuides::route('/'),
            'create' => Pages\CreateSizeGuide::route('/create'),
            'edit' => Pages\EditSizeGuide::route('/{record}/edit'),
        ];
    }
}

