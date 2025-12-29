<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceMatrixResource\Pages;
use App\Filament\Resources\PriceMatrixResource\RelationManagers\RulesRelationManager;
use App\Filament\Resources\PriceMatrixResource\RelationManagers\TiersRelationManager;
use App\Models\PriceMatrix;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceMatrixResource extends Resource
{
    protected static ?string $model = PriceMatrix::class;

    protected static ?string $slug = 'ops-price-matrices';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Price Matrices';

    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product',
                'productVariant',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Matrix')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                $record->translate('name') ?? "Product #{$record->id}"
                            ))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('product_variant_id')
                            ->label('Variant (optional)')
                            ->relationship('productVariant', 'sku')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Select::make('matrix_type')
                            ->options([
                                'quantity' => 'Quantity',
                                'customer_group' => 'Customer group',
                                'region' => 'Region',
                                'mixed' => 'Mixed',
                                'rule_based' => 'Rule-based',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Availability window')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Rules (JSON)')
                    ->schema([
                        Forms\Components\Textarea::make('rules')
                            ->rows(8)
                            ->helperText('For rule-based matrices. Leave blank for an empty config.')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '[]')
                            ->dehydrateStateUsing(function ($state) {
                                if ($state === null || trim((string) $state) === '') {
                                    return [];
                                }

                                $decoded = json_decode((string) $state, true);
                                return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                            }),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Mix & match')
                    ->schema([
                        Forms\Components\Toggle::make('allow_mix_match')
                            ->default(false),

                        Forms\Components\Textarea::make('mix_match_variants')
                            ->label('Mix variants (JSON array of variant IDs)')
                            ->rows(4)
                            ->helperText('Example: [123, 456]')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '[]')
                            ->dehydrateStateUsing(function ($state) {
                                if ($state === null || trim((string) $state) === '') {
                                    return [];
                                }

                                $decoded = json_decode((string) $state, true);
                                return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                            }),

                        Forms\Components\TextInput::make('mix_match_min_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Order constraints')
                    ->schema([
                        Forms\Components\TextInput::make('min_order_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),

                        Forms\Components\TextInput::make('max_order_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Approval')
                    ->schema([
                        Forms\Components\Toggle::make('requires_approval')
                            ->default(false),

                        Forms\Components\Select::make('approval_status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('approved_at')
                            ->disabled()
                            ->hidden(fn (?PriceMatrix $record): bool => blank($record?->approved_at)),

                        Forms\Components\Textarea::make('approval_notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (PriceMatrix $record): string => (string) (
                        $record->product?->translate('name') ?? "Product #{$record->product_id}"
                    ))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('Variant SKU')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('matrix_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_approval')
                    ->boolean()
                    ->label('Approval')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\SelectFilter::make('matrix_type')
                    ->options([
                        'quantity' => 'Quantity',
                        'customer_group' => 'Customer group',
                        'region' => 'Region',
                        'mixed' => 'Mixed',
                        'rule_based' => 'Rule-based',
                    ]),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PriceMatrix $record): bool => (bool) $record->requires_approval && $record->approval_status !== 'approved')
                    ->requiresConfirmation()
                    ->action(function (PriceMatrix $record): void {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth('web')->id(),
                            'approved_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PriceMatrix $record): bool => (bool) $record->requires_approval && $record->approval_status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(function (PriceMatrix $record): void {
                        $record->update([
                            'approval_status' => 'rejected',
                            'approved_by' => auth('web')->id(),
                            'approved_at' => now(),
                        ]);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            TiersRelationManager::class,
            RulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceMatrices::route('/'),
            'create' => Pages\CreatePriceMatrix::route('/create'),
            'view' => Pages\ViewPriceMatrix::route('/{record}'),
            'edit' => Pages\EditPriceMatrix::route('/{record}/edit'),
        ];
    }
}

