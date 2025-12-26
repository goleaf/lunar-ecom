<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralGroupOverrideResource\Pages;
use App\Models\ReferralGroupOverride;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralGroupOverrideResource extends Resource
{
    protected static ?string $model = ReferralGroupOverride::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 14;

    protected static ?string $navigationLabel = 'Group Overrides';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group Selection')
                    ->schema([
                        Forms\Components\Select::make('user_group_id')
                            ->relationship('userGroup', 'name')
                            ->searchable()
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
                            ->helperText('Override the reward value for this group'),

                        Forms\Components\Select::make('stacking_mode_override')
                            ->label('Stacking Mode Override')
                            ->options([
                                \App\Models\ReferralRule::STACKING_EXCLUSIVE => 'Exclusive',
                                \App\Models\ReferralRule::STACKING_BEST_OF => 'Best Of',
                                \App\Models\ReferralRule::STACKING_STACKABLE => 'Stackable',
                            ])
                            ->helperText('Override stacking mode for this group'),

                        Forms\Components\TextInput::make('max_redemptions_override')
                            ->label('Max Redemptions Override')
                            ->numeric()
                            ->helperText('Override max redemptions limit for this group'),

                        Forms\Components\Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true)
                            ->helperText('Enable referral program for this group'),

                        Forms\Components\KeyValue::make('auto_vip_tiers')
                            ->label('Auto VIP Tiers')
                            ->helperText('Format: {"5": "VIP", "10": "Premium"} - Referral count: Tier name')
                            ->keyLabel('Referral Count')
                            ->valueLabel('Tier Name'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('userGroup.name')
                    ->label('Group')
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

                Tables\Columns\IconColumn::make('enabled')
                    ->boolean()
                    ->label('Enabled'),

                Tables\Columns\TextColumn::make('auto_vip_tiers')
                    ->label('Auto VIP Tiers')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state) : 'None'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_group_id')
                    ->relationship('userGroup', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('referral_program_id')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Enabled'),
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralGroupOverrides::route('/'),
            'create' => Pages\CreateReferralGroupOverride::route('/create'),
            'edit' => Pages\EditReferralGroupOverride::route('/{record}/edit'),
        ];
    }
}


