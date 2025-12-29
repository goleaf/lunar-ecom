<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductScheduleResource\Pages;
use App\Filament\Resources\ProductScheduleResource\RelationManagers\HistoryRelationManager;
use App\Models\ProductSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductScheduleResource extends Resource
{
    protected static ?string $model = ProductSchedule::class;

    protected static ?string $slug = 'ops-product-schedules';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Product Schedules';

    protected static ?int $navigationSort = 60;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                $record->translateAttribute('name') ?? "Product #{$record->id}"
                            ))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'publish' => 'Publish',
                                'unpublish' => 'Unpublish',
                                'flash_sale' => 'Flash sale',
                                'seasonal' => 'Seasonal',
                                'time_limited' => 'Time-limited',
                            ])
                            ->required()
                            ->default('publish'),

                        Forms\Components\Select::make('schedule_type')
                            ->options([
                                'one_time' => 'One-time',
                                'recurring' => 'Recurring',
                            ])
                            ->required()
                            ->default('one_time'),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->nullable(),

                        Forms\Components\TextInput::make('target_status')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('Optional override; defaults depend on schedule type.'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Flash sale')
                    ->schema([
                        Forms\Components\TextInput::make('sale_price')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        Forms\Components\TextInput::make('sale_percentage')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),

                        Forms\Components\Toggle::make('restore_original_price')
                            ->default(true),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Recurring rules')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->default(false),

                        Forms\Components\Select::make('recurrence_pattern')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->nullable(),

                        Forms\Components\TextInput::make('timezone')
                            ->maxLength(64)
                            ->default('UTC')
                            ->nullable(),

                        Forms\Components\TextInput::make('days_of_week')
                            ->label('Days of week (JSON array)')
                            ->helperText('Example: [1,2,3,4,5] for Mon-Fri (0=Sun, 6=Sat).')
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
                            ->nullable(),

                        Forms\Components\TimePicker::make('time_start')
                            ->nullable(),

                        Forms\Components\TimePicker::make('time_end')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Notifications')
                    ->schema([
                        Forms\Components\Toggle::make('send_notification')
                            ->default(false),

                        Forms\Components\TextInput::make('notification_hours_before')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('notification_sent_at')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('notification_scheduled_at')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Execution')
                    ->schema([
                        Forms\Components\DateTimePicker::make('executed_at')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Toggle::make('execution_success')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('execution_error')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
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
                    ->getStateUsing(fn (ProductSchedule $record): string => (string) (
                        $record->product?->translateAttribute('name') ?? "Product #{$record->product_id}"
                    ))
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedule_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('executed_at')
                    ->dateTime()
                    ->label('Executed')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'publish' => 'Publish',
                        'unpublish' => 'Unpublish',
                        'flash_sale' => 'Flash sale',
                        'seasonal' => 'Seasonal',
                        'time_limited' => 'Time-limited',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('executed_at')
                    ->label('Executed')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('executed_at'),
                        false: fn (Builder $query) => $query->whereNull('executed_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductSchedules::route('/'),
            'create' => Pages\CreateProductSchedule::route('/create'),
            'view' => Pages\ViewProductSchedule::route('/{record}'),
            'edit' => Pages\EditProductSchedule::route('/{record}/edit'),
        ];
    }
}

