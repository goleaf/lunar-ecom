<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralProgramResource\Pages;
use App\Filament\Resources\ReferralProgramResource\RelationManagers;
use App\Models\ReferralProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Models\CustomerGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;

class ReferralProgramResource extends Resource
{
    protected static ?string $model = ReferralProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Program Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('handle')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this program'),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),

                        DatePicker::make('starts_at')
                            ->label('Start Date')
                            ->nullable(),

                        DatePicker::make('ends_at')
                            ->label('End Date')
                            ->nullable()
                            ->helperText('Leave empty for programs without expiry'),
                    ])
                    ->columns(2),

                Section::make('Eligibility Rules')
                    ->schema([
                        Select::make('eligible_customer_groups')
                            ->label('Eligible Customer Groups')
                            ->multiple()
                            ->options(CustomerGroup::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty to allow all customer groups'),

                        Forms\Components\TagsInput::make('eligible_users')
                            ->label('Eligible User IDs')
                            ->helperText('Specific user IDs (comma-separated). Leave empty for all users.'),

                        KeyValue::make('eligible_conditions')
                            ->label('Custom Eligibility Conditions')
                            ->helperText('Custom conditions (e.g., min_orders, min_spend)'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Referrer Rewards')
                    ->schema([
                        Repeater::make('referrer_rewards')
                            ->schema([
                                Select::make('action')
                                    ->options([
                                        'signup' => 'Signup',
                                        'first_purchase' => 'First Purchase',
                                        'repeat_purchase' => 'Repeat Purchase',
                                    ])
                                    ->required(),

                                Select::make('type')
                                    ->options([
                                        'discount' => 'Discount Code',
                                        'credit' => 'Account Credit',
                                        'percentage' => 'Percentage Discount',
                                        'fixed_amount' => 'Fixed Amount',
                                    ])
                                    ->required(),

                                TextInput::make('value')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Percentage or amount'),

                                Select::make('currency_id')
                                    ->relationship('currency', 'code', fn ($query) => $query->where('enabled', true))
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('max_per_order')
                                    ->numeric()
                                    ->label('Max Per Order')
                                    ->helperText('Maximum discount per order (for percentage)'),

                                TextInput::make('coupon_code')
                                    ->label('Custom Coupon Code')
                                    ->maxLength(255)
                                    ->helperText('Leave empty to auto-generate'),

                                TextInput::make('valid_days')
                                    ->numeric()
                                    ->default(30)
                                    ->label('Valid Days')
                                    ->helperText('How long the reward is valid'),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->collapsible(),
                    ])
                    ->collapsible(),

                Section::make('Referee (Invited User) Rewards')
                    ->schema([
                        Repeater::make('referee_rewards')
                            ->schema([
                                Select::make('type')
                                    ->options([
                                        'discount' => 'Discount Code',
                                        'percentage' => 'Percentage Discount',
                                        'fixed_amount' => 'Fixed Amount',
                                    ])
                                    ->required(),

                                TextInput::make('value')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('coupon_code')
                                    ->label('Coupon Code')
                                    ->maxLength(255)
                                    ->helperText('Leave empty to auto-generate'),

                                TextInput::make('valid_days')
                                    ->numeric()
                                    ->default(30)
                                    ->label('Valid Days'),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->collapsible(),
                    ])
                    ->collapsible(),

                Section::make('Limits & Restrictions')
                    ->schema([
                        TextInput::make('max_referrals_per_referrer')
                            ->numeric()
                            ->label('Max Referrals Per Referrer')
                            ->helperText('Leave empty for unlimited'),

                        TextInput::make('max_referrals_total')
                            ->numeric()
                            ->label('Max Total Referrals')
                            ->helperText('Leave empty for unlimited'),

                        TextInput::make('max_rewards_per_referrer')
                            ->numeric()
                            ->label('Max Rewards Per Referrer')
                            ->helperText('Leave empty for unlimited'),

                        Toggle::make('allow_self_referral')
                            ->label('Allow Self-Referral')
                            ->default(false),

                        Toggle::make('require_referee_purchase')
                            ->label('Require Referee Purchase')
                            ->default(false)
                            ->helperText('Require purchase before issuing reward'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Stacking & Validity')
                    ->schema([
                        Select::make('stacking_mode')
                            ->options([
                                'non_stackable' => 'Non-Stackable',
                                'stackable' => 'Stackable',
                                'exclusive' => 'Exclusive',
                            ])
                            ->default('non_stackable')
                            ->required(),

                        KeyValue::make('stacking_rules')
                            ->label('Custom Stacking Rules')
                            ->helperText('Additional stacking configuration'),

                        TextInput::make('referral_code_validity_days')
                            ->numeric()
                            ->default(365)
                            ->label('Code Validity (Days)')
                            ->required(),

                        TextInput::make('reward_validity_days')
                            ->numeric()
                            ->label('Reward Validity (Days)')
                            ->helperText('Leave empty to use program default'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Metadata')
                    ->schema([
                        KeyValue::make('meta')
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

                Tables\Columns\TextColumn::make('handle')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->date()
                    ->sortable()
                    ->placeholder('No expiry'),

                Tables\Columns\TextColumn::make('total_referrals')
                    ->label('Referrals')
                    ->sortable()
                    ->default(0),

                Tables\Columns\TextColumn::make('total_rewards_issued')
                    ->label('Rewards')
                    ->sortable()
                    ->default(0),

                Tables\Columns\TextColumn::make('total_reward_value')
                    ->money('EUR')
                    ->sortable()
                    ->default(0),

                Tables\Columns\TextColumn::make('referral_codes_count')
                    ->counts('referralCodes')
                    ->label('Codes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\Filter::make('starts_at')
                    ->form([
                        DatePicker::make('starts_from')
                            ->label('Starts From'),
                        DatePicker::make('starts_until')
                            ->label('Starts Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['starts_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['starts_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '>=', now())
                        ->where('ends_at', '<=', now()->addDays(30))
                        ->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\Action::make('view_analytics')
                    ->label('Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (ReferralProgram $record): string => ReferralProgramResource::getUrl('analytics', ['record' => $record])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReferralCodesRelationManager::class,
            RelationManagers\EventsRelationManager::class,
            RelationManagers\RewardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralPrograms::route('/'),
            'create' => Pages\CreateReferralProgram::route('/create'),
            'view' => Pages\ViewReferralProgram::route('/{record}'),
            'edit' => Pages\EditReferralProgram::route('/{record}/edit'),
            'analytics' => Pages\ReferralProgramAnalytics::route('/{record}/analytics'),
        ];
    }
}

