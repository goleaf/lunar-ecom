<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAvailabilityResource\Pages\CreateProductAvailability;
use App\Filament\Resources\ProductAvailabilityResource\Pages\EditProductAvailability;
use App\Filament\Resources\ProductAvailabilityResource\Pages\ListProductAvailabilities;
use App\Filament\Resources\ProductAvailabilityResource\Pages\ViewProductAvailability;
use App\Models\ProductAvailability;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductAvailabilityResource extends Resource
{
    protected static ?string $model = ProductAvailability::class;

    protected static ?string $slug = 'ops-product-availability';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Product Availability';

    protected static ?int $navigationSort = 75;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'productVariant']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Availability rule')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->relationship('product', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                            $record->translateAttribute('name') ?? "Product #{$record->id}"
                        ))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('product_variant_id')
                        ->relationship('productVariant', 'sku')
                        ->searchable()
                        ->nullable()
                        ->helperText('Optional: restrict to a specific variant.'),

                    Forms\Components\Select::make('availability_type')
                        ->options([
                            'always_available' => 'Always available',
                            'date_range' => 'Date range',
                            'specific_dates' => 'Specific dates',
                            'recurring' => 'Recurring',
                        ])
                        ->default('always_available')
                        ->required(),

                    Forms\Components\Toggle::make('is_active')->default(true),

                    Forms\Components\TextInput::make('timezone')
                        ->default('UTC')
                        ->maxLength(64),

                    Forms\Components\TextInput::make('priority')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ])
                ->columns(3),

            Forms\Components\Section::make('Date range')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')->nullable(),
                    Forms\Components\DatePicker::make('end_date')->nullable(),
                ])
                ->columns(2)
                ->collapsed(),

            Forms\Components\Section::make('Specific dates')
                ->schema([
                    Forms\Components\Textarea::make('available_dates')
                        ->label('Available dates (JSON array)')
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
                            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        })
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('unavailable_dates')
                        ->label('Unavailable dates (JSON array)')
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
                            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        })
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->collapsed(),

            Forms\Components\Section::make('Recurring')
                ->schema([
                    Forms\Components\Toggle::make('is_recurring')->default(false),

                    Forms\Components\Textarea::make('recurrence_pattern')
                        ->label('Recurrence pattern (JSON)')
                        ->rows(4)
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                        ->dehydrateStateUsing(function ($state) {
                            if (is_array($state)) {
                                return $state;
                            }
                            if (! is_string($state) || trim($state) === '') {
                                return null;
                            }
                            $decoded = json_decode($state, true);
                            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        })
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->collapsed(),

            Forms\Components\Section::make('Quantity / time limits')
                ->schema([
                    Forms\Components\TextInput::make('max_quantity_per_date')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                    Forms\Components\TextInput::make('total_quantity')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                    Forms\Components\TimePicker::make('available_from')->nullable(),
                    Forms\Components\TimePicker::make('available_until')->nullable(),
                    Forms\Components\TextInput::make('slot_duration_minutes')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                ])
                ->columns(3)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (ProductAvailability $record): string => (string) (
                        $record->product?->translateAttribute('name') ?? "Product #{$record->product_id}"
                    ))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('product_variant_id')
                    ->label('Variant')
                    ->getStateUsing(fn (ProductAvailability $record): string => $record->productVariant?->sku ?? ($record->product_variant_id ? "#{$record->product_variant_id}" : 'All'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('availability_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('availability_type')
                    ->options([
                        'always_available' => 'Always available',
                        'date_range' => 'Date range',
                        'specific_dates' => 'Specific dates',
                        'recurring' => 'Recurring',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductAvailabilities::route('/'),
            'create' => CreateProductAvailability::route('/create'),
            'view' => ViewProductAvailability::route('/{record}'),
            'edit' => EditProductAvailability::route('/{record}/edit'),
        ];
    }
}

