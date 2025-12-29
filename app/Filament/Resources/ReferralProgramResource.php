<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralProgramResource\Pages;
use App\Filament\Resources\ReferralProgramResource\RelationManagers;
use App\Models\ReferralProgram;
use App\Models\ReferralRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Models\CustomerGroup;

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
                Forms\Components\Section::make('Program Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('handle')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this program'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                ReferralProgram::STATUS_DRAFT => 'Draft',
                                ReferralProgram::STATUS_ACTIVE => 'Active',
                                ReferralProgram::STATUS_PAUSED => 'Paused',
                                ReferralProgram::STATUS_ARCHIVED => 'Archived',
                            ])
                            ->default(ReferralProgram::STATUS_DRAFT)
                            ->required()
                            ->helperText('Draft programs are not visible to users. Activate to go live.'),

                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('Start Date & Time')
                            ->timezone('UTC')
                            ->nullable()
                            ->helperText('Program becomes active at this time'),

                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('End Date & Time')
                            ->timezone('UTC')
                            ->nullable()
                            ->helperText('Program expires at this time. Leave empty for no expiry.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Channel & Currency Scope')
                    ->schema([
                        Forms\Components\TagsInput::make('channel_ids')
                            ->label('Channel IDs')
                            ->helperText('Leave empty for all channels'),

                        Forms\Components\Select::make('currency_scope')
                            ->options([
                                ReferralProgram::CURRENCY_SCOPE_ALL => 'All Currencies',
                                ReferralProgram::CURRENCY_SCOPE_SPECIFIC => 'Specific Currencies',
                            ])
                            ->default(ReferralProgram::CURRENCY_SCOPE_ALL)
                            ->required()
                            ->reactive(),

                        Forms\Components\TagsInput::make('currency_ids')
                            ->label('Currency IDs')
                            ->visible(fn ($get) => $get('currency_scope') === ReferralProgram::CURRENCY_SCOPE_SPECIFIC)
                            ->helperText('Comma-separated currency IDs'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Audience Scope')
                    ->schema([
                        Forms\Components\Select::make('audience_scope')
                            ->options([
                                ReferralProgram::AUDIENCE_SCOPE_ALL => 'All Users',
                                ReferralProgram::AUDIENCE_SCOPE_USERS => 'Specific Users',
                                ReferralProgram::AUDIENCE_SCOPE_GROUPS => 'User Groups',
                            ])
                            ->default(ReferralProgram::AUDIENCE_SCOPE_ALL)
                            ->required()
                            ->reactive(),

                        Forms\Components\TagsInput::make('audience_user_ids')
                            ->label('User IDs')
                            ->visible(fn ($get) => $get('audience_scope') === ReferralProgram::AUDIENCE_SCOPE_USERS)
                            ->helperText('Comma-separated user IDs'),

                        Forms\Components\Select::make('audience_group_ids')
                            ->label('User Groups')
                            ->multiple()
                            ->options(\App\Models\UserGroup::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('audience_scope') === ReferralProgram::AUDIENCE_SCOPE_GROUPS),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Attribution Settings')
                    ->schema([
                        Forms\Components\Toggle::make('last_click_wins')
                            ->label('Last Click Wins')
                            ->default(true)
                            ->helperText('If enabled, last referral click overwrites previous. If disabled, first click is used.'),

                        Forms\Components\TextInput::make('attribution_ttl_days')
                            ->label('Attribution TTL (Days)')
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(365)
                            ->required()
                            ->helperText('How long referral clicks remain valid for attribution'),

                        Forms\Components\TextInput::make('referral_code_validity_days')
                            ->numeric()
                            ->default(365)
                            ->label('Code Validity (Days)')
                            ->required()
                            ->helperText('How long referral codes remain valid'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Discount Stacking & Priority')
                    ->schema([
                        Forms\Components\Select::make('default_stacking_mode')
                            ->label('Default Stacking Mode')
                            ->options([
                                ReferralRule::STACKING_EXCLUSIVE => 'Exclusive (No stacking)',
                                ReferralRule::STACKING_BEST_OF => 'Best Of (Largest discount)',
                                ReferralRule::STACKING_STACKABLE => 'Stackable (Allow stacking)',
                            ])
                            ->default(ReferralRule::STACKING_EXCLUSIVE)
                            ->required()
                            ->helperText('Default stacking mode for all rules in this program'),

                        Forms\Components\TextInput::make('max_total_discount_percent')
                            ->label('Max Total Discount (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Maximum total discount percentage when stacking (e.g., 20% max)'),

                        Forms\Components\TextInput::make('max_total_discount_amount')
                            ->label('Max Total Discount Amount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Maximum total discount amount when stacking'),

                        Forms\Components\Toggle::make('apply_before_tax')
                            ->label('Apply Before Tax')
                            ->default(true)
                            ->helperText('If enabled, discount applies to subtotal before tax calculation'),

                        Forms\Components\Toggle::make('shipping_discount_stacks')
                            ->label('Shipping Discount Stacks')
                            ->default(false)
                            ->helperText('If enabled, shipping discounts can stack with referral discounts'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('meta')
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

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('End Date')
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

                Tables\Columns\TextColumn::make('rules_count')
                    ->counts('rules')
                    ->label('Rules')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attributions_count')
                    ->counts('attributions')
                    ->label('Attributions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReferralProgram::STATUS_DRAFT => 'Draft',
                        ReferralProgram::STATUS_ACTIVE => 'Active',
                        ReferralProgram::STATUS_PAUSED => 'Paused',
                        ReferralProgram::STATUS_ARCHIVED => 'Archived',
                    ]),

                Tables\Filters\Filter::make('start_at')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start From'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_at', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn (Builder $query): Builder => $query->where('end_at', '>=', now())
                        ->where('end_at', '<=', now()->addDays(30))
                        ->where('status', ReferralProgram::STATUS_ACTIVE)),
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
            RelationManagers\ReferralRulesRelationManager::class,
            RelationManagers\AttributionsRelationManager::class,
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

