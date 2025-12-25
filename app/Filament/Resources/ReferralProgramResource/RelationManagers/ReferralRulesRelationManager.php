<?php

namespace App\Filament\Resources\ReferralProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ReferralRule;

class ReferralRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('trigger_event')
                    ->options([
                        ReferralRule::TRIGGER_SIGNUP => 'Signup',
                        ReferralRule::TRIGGER_FIRST_ORDER_PAID => 'First Order Paid',
                        ReferralRule::TRIGGER_NTH_ORDER_PAID => 'Nth Order Paid',
                        ReferralRule::TRIGGER_SUBSCRIPTION_STARTED => 'Subscription Started',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('nth_order')
                    ->numeric()
                    ->label('Nth Order')
                    ->visible(fn ($get) => $get('trigger_event') === ReferralRule::TRIGGER_NTH_ORDER_PAID)
                    ->required(fn ($get) => $get('trigger_event') === ReferralRule::TRIGGER_NTH_ORDER_PAID),

                Forms\Components\Section::make('Referee Rewards')
                    ->schema([
                        Forms\Components\Select::make('referee_reward_type')
                            ->options([
                                ReferralRule::REWARD_COUPON => 'Coupon',
                                ReferralRule::REWARD_PERCENTAGE_DISCOUNT => 'Percentage Discount',
                                ReferralRule::REWARD_FIXED_DISCOUNT => 'Fixed Discount',
                                ReferralRule::REWARD_FREE_SHIPPING => 'Free Shipping',
                                ReferralRule::REWARD_STORE_CREDIT => 'Store Credit',
                            ])
                            ->reactive(),

                        Forms\Components\TextInput::make('referee_reward_value')
                            ->numeric()
                            ->label('Value')
                            ->required(fn ($get) => $get('referee_reward_type') !== null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Referrer Rewards')
                    ->schema([
                        Forms\Components\Select::make('referrer_reward_type')
                            ->options([
                                ReferralRule::REWARD_COUPON => 'Coupon',
                                ReferralRule::REWARD_STORE_CREDIT => 'Store Credit',
                                ReferralRule::REWARD_PERCENTAGE_DISCOUNT_NEXT_ORDER => 'Percentage Discount (Next Order)',
                                ReferralRule::REWARD_FIXED_AMOUNT => 'Fixed Amount',
                            ])
                            ->reactive(),

                        Forms\Components\TextInput::make('referrer_reward_value')
                            ->numeric()
                            ->label('Value')
                            ->required(fn ($get) => $get('referrer_reward_type') !== null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Eligibility')
                    ->schema([
                        Forms\Components\TextInput::make('min_order_total')
                            ->numeric()
                            ->label('Min Order Total')
                            ->prefix('€'),

                        Forms\Components\TagsInput::make('eligible_product_ids')
                            ->label('Product IDs'),

                        Forms\Components\TagsInput::make('eligible_category_ids')
                            ->label('Category IDs'),

                        Forms\Components\TagsInput::make('eligible_collection_ids')
                            ->label('Collection IDs'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_redemptions_total')
                            ->numeric()
                            ->label('Max Total Redemptions'),

                        Forms\Components\TextInput::make('max_redemptions_per_referrer')
                            ->numeric()
                            ->label('Max Per Referrer'),

                        Forms\Components\TextInput::make('max_redemptions_per_referee')
                            ->numeric()
                            ->label('Max Per Referee'),

                        Forms\Components\TextInput::make('cooldown_days')
                            ->numeric()
                            ->label('Cooldown (Days)'),

                        Forms\Components\TextInput::make('validation_window_days')
                            ->numeric()
                            ->label('Validation Window (Days)')
                            ->helperText('Referee must order within X days'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Stacking & Priority')
                    ->schema([
                        Forms\Components\Select::make('stacking_mode')
                            ->options([
                                ReferralRule::STACKING_EXCLUSIVE => 'Exclusive',
                                ReferralRule::STACKING_STACKABLE => 'Stackable',
                                ReferralRule::STACKING_BEST_OF => 'Best Of',
                            ])
                            ->default(ReferralRule::STACKING_EXCLUSIVE)
                            ->required(),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Higher priority rules are applied first'),

                        Forms\Components\Select::make('fraud_policy_id')
                            ->relationship('fraudPolicy', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\KeyValue::make('tiered_rewards')
                            ->label('Tiered Rewards')
                            ->helperText('Format: {"1": 5.00, "5": 10.00, "10": 25.00} - Threshold: Amount')
                            ->visible(fn ($get) => $get('referrer_reward_type') === ReferralRule::REWARD_STORE_CREDIT || 
                                                   $get('referrer_reward_type') === ReferralRule::REWARD_FIXED_AMOUNT),

                        Forms\Components\TextInput::make('coupon_validity_days')
                            ->numeric()
                            ->default(30)
                            ->label('Coupon Validity (Days)')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('trigger_event')
            ->columns([
                Tables\Columns\TextColumn::make('trigger_event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralRule::TRIGGER_SIGNUP => 'success',
                        ReferralRule::TRIGGER_FIRST_ORDER_PAID => 'info',
                        ReferralRule::TRIGGER_NTH_ORDER_PAID => 'warning',
                        ReferralRule::TRIGGER_SUBSCRIPTION_STARTED => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('nth_order')
                    ->label('Nth Order')
                    ->sortable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('referee_reward_type')
                    ->label('Referee Reward')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referee_reward_value')
                    ->label('Referee Value')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('referrer_reward_type')
                    ->label('Referrer Reward')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referrer_reward_value')
                    ->label('Referrer Value')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_event')
                    ->options([
                        ReferralRule::TRIGGER_SIGNUP => 'Signup',
                        ReferralRule::TRIGGER_FIRST_ORDER_PAID => 'First Order Paid',
                        ReferralRule::TRIGGER_NTH_ORDER_PAID => 'Nth Order Paid',
                        ReferralRule::TRIGGER_SUBSCRIPTION_STARTED => 'Subscription Started',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            ->defaultSort('priority', 'desc');
    }
}

