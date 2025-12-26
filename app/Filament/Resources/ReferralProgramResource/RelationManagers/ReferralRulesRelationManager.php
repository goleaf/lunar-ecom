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
                            ->label('Stacking Mode')
                            ->options([
                                ReferralRule::STACKING_EXCLUSIVE => 'Exclusive (No stacking with other discounts)',
                                ReferralRule::STACKING_STACKABLE => 'Stackable (Allow stacking with caps)',
                                ReferralRule::STACKING_BEST_OF => 'Best Of (Choose largest discount)',
                            ])
                            ->default(ReferralRule::STACKING_EXCLUSIVE)
                            ->required()
                            ->reactive()
                            ->helperText('How this discount interacts with other promotions'),

                        Forms\Components\TextInput::make('max_total_discount_percent')
                            ->label('Max Total Discount (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->visible(fn ($get) => $get('stacking_mode') === ReferralRule::STACKING_STACKABLE)
                            ->helperText('Maximum total discount percentage when stacking (e.g., 20% max)'),

                        Forms\Components\TextInput::make('max_total_discount_amount')
                            ->label('Max Total Discount Amount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€')
                            ->visible(fn ($get) => $get('stacking_mode') === ReferralRule::STACKING_STACKABLE)
                            ->helperText('Maximum total discount amount when stacking'),

                        Forms\Components\Toggle::make('apply_before_tax')
                            ->label('Apply Before Tax')
                            ->default(true)
                            ->helperText('If enabled, discount applies to subtotal before tax calculation'),

                        Forms\Components\Toggle::make('shipping_discount_stacks')
                            ->label('Shipping Discount Stacks')
                            ->default(false)
                            ->helperText('If enabled, shipping discounts can stack with this referral discount'),

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
                    ->sortable()
                    ->label('Priority')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 10 => 'success',
                        $state >= 5 => 'info',
                        $state >= 0 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),
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
                Tables\Actions\Action::make('test_rule')
                    ->label('Test Rule')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Test User')
                            ->relationship('program.referee', 'email')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('order_total')
                            ->label('Order Total')
                            ->numeric()
                            ->default(100)
                            ->prefix('€'),
                    ])
                    ->action(function (ReferralRule $record, array $data) {
                        // Simulate rule application
                        $user = \App\Models\User::find($data['user_id']);
                        $orderTotal = $data['order_total'];

                        $applicable = true;
                        $reasons = [];

                        if ($record->min_order_total && $orderTotal < $record->min_order_total) {
                            $applicable = false;
                            $reasons[] = "Order total ({$orderTotal}) below minimum ({$record->min_order_total})";
                        }

                        \Filament\Notifications\Notification::make()
                            ->title($applicable ? 'Rule Would Apply' : 'Rule Would Not Apply')
                            ->body($applicable ? 'This rule would be triggered for this scenario.' : implode(', ', $reasons))
                            ->{$applicable ? 'success' : 'warning'}()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            ReferralRule::whereIn('id', $records->pluck('id'))
                                ->update(['is_active' => true]);
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            ReferralRule::whereIn('id', $records->pluck('id'))
                                ->update(['is_active' => false]);
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

