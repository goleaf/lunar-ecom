<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockNotificationResource\Pages;
use App\Models\StockNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockNotificationResource extends Resource
{
    protected static ?string $model = StockNotification::class;

    protected static ?string $slug = 'ops-stock-notifications';

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Stock Notifications';

    protected static ?int $navigationSort = 60;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product',
                'productVariant.product',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'sent' => 'Sent',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('notification_count')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('notified_at')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Preferences')
                    ->schema([
                        Forms\Components\Toggle::make('notify_on_backorder')
                            ->default(false),

                        Forms\Components\TextInput::make('min_quantity')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Only notify when stock is at or above this amount.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Target')
                    ->schema([
                        Forms\Components\TextInput::make('product_id')
                            ->label('Product ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('product_name')
                            ->label('Product')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (StockNotification $record): string => (string) (
                                $record->product?->translate('name') ?? "Product #{$record->product_id}"
                            )),

                        Forms\Components\TextInput::make('productVariant.sku')
                            ->label('Variant SKU')
                            ->disabled(),

                        Forms\Components\TextInput::make('token')
                            ->disabled()
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (StockNotification $record): string => (string) (
                        $record->product?->translate('name') ?? "Product #{$record->product_id}"
                    ))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('Variant SKU')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('notification_count')
                    ->label('Sent #')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('notify_on_backorder')
                    ->label('Backorder')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('min_quantity')
                    ->label('Min qty')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (StockNotification $record): bool => $record->status === 'pending')
                    ->action(fn (StockNotification $record) => $record->cancel()),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockNotifications::route('/'),
            'edit' => Pages\EditStockNotification::route('/{record}/edit'),
        ];
    }
}

