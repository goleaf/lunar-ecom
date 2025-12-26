<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralUserOverrideResource\Pages;
use App\Models\ReferralUserOverride;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralUserOverrideResource extends Resource
{
    protected static ?string $model = ReferralUserOverride::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = 'User Overrides';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Selection')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'email')
                            ->searchable(['email', 'name'])
                            ->required()
                            ->preload()
                            ->reactive(),

                        Forms\Components\Select::make('referral_program_id')
                            ->label('Program')
                            ->relationship('program', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty to apply to all programs'),

                        Forms\Components\Select::make('referral_rule_id')
                            ->label('Rule')
                            ->relationship('rule', 'trigger_event', fn ($query, $get) => 
                                $query->when($get('referral_program_id'), fn ($q, $programId) => 
                                    $q->where('referral_program_id', $programId)
                                )
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty to apply to all rules'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Overrides')
                    ->schema([
                        Forms\Components\TextInput::make('reward_value_override')
                            ->label('Reward Value Override')
                            ->numeric()
                            ->prefix('€')
                            ->helperText('Override the reward value for this user'),

                        Forms\Components\Select::make('stacking_mode_override')
                            ->label('Stacking Mode Override')
                            ->options([
                                \App\Models\ReferralRule::STACKING_EXCLUSIVE => 'Exclusive',
                                \App\Models\ReferralRule::STACKING_BEST_OF => 'Best Of',
                                \App\Models\ReferralRule::STACKING_STACKABLE => 'Stackable',
                            ])
                            ->helperText('Override stacking mode for this user'),

                        Forms\Components\TextInput::make('max_redemptions_override')
                            ->label('Max Redemptions Override')
                            ->numeric()
                            ->helperText('Override max redemptions limit for this user'),

                        Forms\Components\Toggle::make('block_referrals')
                            ->label('Block Referrals')
                            ->helperText('Prevent this user from earning referral rewards'),

                        Forms\Components\Select::make('vip_tier')
                            ->label('VIP Tier')
                            ->options([
                                'bronze' => 'Bronze',
                                'silver' => 'Silver',
                                'gold' => 'Gold',
                                'platinum' => 'Platinum',
                                'vip' => 'VIP',
                            ])
                            ->helperText('Manually set VIP tier for this user'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable()
                    ->placeholder('All Programs'),

                Tables\Columns\TextColumn::make('rule.trigger_event')
                    ->label('Rule')
                    ->badge()
                    ->placeholder('All Rules'),

                Tables\Columns\TextColumn::make('reward_value_override')
                    ->label('Reward Override')
                    ->money('EUR')
                    ->placeholder('No override'),

                Tables\Columns\TextColumn::make('stacking_mode_override')
                    ->label('Stacking Override')
                    ->badge()
                    ->placeholder('No override'),

                Tables\Columns\TextColumn::make('max_redemptions_override')
                    ->label('Max Redemptions')
                    ->placeholder('No override'),

                Tables\Columns\IconColumn::make('block_referrals')
                    ->boolean()
                    ->label('Blocked'),

                Tables\Columns\TextColumn::make('vip_tier')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'vip', 'platinum' => 'success',
                        'gold' => 'warning',
                        'silver', 'bronze' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('referral_program_id')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('block_referrals')
                    ->label('Blocked'),

                Tables\Filters\SelectFilter::make('vip_tier')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                        'vip' => 'VIP',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('unblock')
                        ->label('Unblock Referrals')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            ReferralUserOverride::whereIn('id', $records->pluck('id'))
                                ->update(['block_referrals' => false]);
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralUserOverrides::route('/'),
            'create' => Pages\CreateReferralUserOverride::route('/create'),
            'edit' => Pages\EditReferralUserOverride::route('/{record}/edit'),
        ];
    }
}


