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

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),

                        Forms\Components\Select::make('status')
                            ->options([
                                ReferralProgram::STATUS_DRAFT => 'Draft',
                                ReferralProgram::STATUS_ACTIVE => 'Active',
                                ReferralProgram::STATUS_PAUSED => 'Paused',
                                ReferralProgram::STATUS_ARCHIVED => 'Archived',
                            ])
                            ->default(ReferralProgram::STATUS_DRAFT)
                            ->required(),

                        Forms\Components\DatePicker::make('start_at')
                            ->label('Start Date')
                            ->nullable(),

                        Forms\Components\DatePicker::make('end_at')
                            ->label('End Date')
                            ->nullable()
                            ->helperText('Leave empty for programs without expiry'),
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

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReferralProgram::STATUS_DRAFT => 'Draft',
                        ReferralProgram::STATUS_ACTIVE => 'Active',
                        ReferralProgram::STATUS_PAUSED => 'Paused',
                        ReferralProgram::STATUS_ARCHIVED => 'Archived',
                    ]),

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

