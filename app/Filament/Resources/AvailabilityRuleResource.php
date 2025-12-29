<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvailabilityRuleResource\Pages\CreateAvailabilityRule;
use App\Filament\Resources\AvailabilityRuleResource\Pages\EditAvailabilityRule;
use App\Filament\Resources\AvailabilityRuleResource\Pages\ListAvailabilityRules;
use App\Filament\Resources\AvailabilityRuleResource\Pages\ViewAvailabilityRule;
use App\Models\AvailabilityRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityRuleResource extends Resource
{
    protected static ?string $model = AvailabilityRule::class;

    protected static ?string $slug = 'ops-availability-rules';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Availability Rules';

    protected static ?int $navigationSort = 76;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rule')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->relationship('product', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                            $record->translateAttribute('name') ?? "Product #{$record->id}"
                        ))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('rule_type')
                        ->options([
                            'minimum_rental_period' => 'Minimum rental period',
                            'maximum_rental_period' => 'Maximum rental period',
                            'lead_time' => 'Lead time',
                            'buffer_time' => 'Buffer time',
                            'cancellation_policy' => 'Cancellation policy',
                            'blackout_date' => 'Blackout date',
                            'special_pricing' => 'Special pricing',
                        ])
                        ->required(),

                    Forms\Components\Textarea::make('rule_config')
                        ->label('Rule config (JSON)')
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

                    Forms\Components\DatePicker::make('rule_start_date')->nullable(),
                    Forms\Components\DatePicker::make('rule_end_date')->nullable(),

                    Forms\Components\TextInput::make('priority')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Forms\Components\Toggle::make('is_active')->default(true),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (AvailabilityRule $record): string => (string) (
                        $record->product?->translateAttribute('name') ?? "Product #{$record->product_id}"
                    ))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('rule_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rule_type')
                    ->options([
                        'minimum_rental_period' => 'Minimum rental period',
                        'maximum_rental_period' => 'Maximum rental period',
                        'lead_time' => 'Lead time',
                        'buffer_time' => 'Buffer time',
                        'cancellation_policy' => 'Cancellation policy',
                        'blackout_date' => 'Blackout date',
                        'special_pricing' => 'Special pricing',
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
            'index' => ListAvailabilityRules::route('/'),
            'create' => CreateAvailabilityRule::route('/create'),
            'view' => ViewAvailabilityRule::route('/{record}'),
            'edit' => EditAvailabilityRule::route('/{record}/edit'),
        ];
    }
}

