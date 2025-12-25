<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FitFeedbackResource\Pages;
use App\Models\FitFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FitFeedbackResource extends Resource
{
    protected static ?string $model = FitFeedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product & Customer')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'translate.name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('order_id')
                            ->label('Order ID')
                            ->nullable(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Size Information')
                    ->schema([
                        Forms\Components\Select::make('size_guide_id')
                            ->relationship('sizeGuide', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('fit_finder_quiz_id')
                            ->relationship('fitFinderQuiz', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('purchased_size')
                            ->label('Purchased Size')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('recommended_size')
                            ->label('Recommended Size')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Fit Feedback')
                    ->schema([
                        Forms\Components\Select::make('actual_fit')
                            ->options([
                                'perfect' => 'Perfect',
                                'too_small' => 'Too Small',
                                'too_large' => 'Too Large',
                                'too_tight' => 'Too Tight',
                                'too_loose' => 'Too Loose',
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('fit_rating')
                            ->options([
                                1 => '1 - Very Poor',
                                2 => '2 - Poor',
                                3 => '3 - Average',
                                4 => '4 - Good',
                                5 => '5 - Excellent',
                            ])
                            ->nullable(),

                        Forms\Components\Textarea::make('feedback_text')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('body_measurements')
                            ->label('Body Measurements (JSON)')
                            ->helperText('Customer body measurements in JSON format')
                            ->rows(3)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Return/Exchange Information')
                    ->schema([
                        Forms\Components\Toggle::make('would_exchange')
                            ->label('Would Exchange')
                            ->default(false),

                        Forms\Components\Toggle::make('would_return')
                            ->label('Would Return')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Moderation')
                    ->schema([
                        Forms\Components\Toggle::make('is_helpful')
                            ->label('Mark as Helpful')
                            ->default(false),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Make Public')
                            ->default(false)
                            ->helperText('Allow this feedback to be shown to other customers'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.translate.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('purchased_size')
                    ->label('Purchased')
                    ->sortable(),

                Tables\Columns\TextColumn::make('recommended_size')
                    ->label('Recommended')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actual_fit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'perfect' => 'success',
                        'too_small', 'too_large', 'too_tight', 'too_loose' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('fit_rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('would_return')
                    ->boolean()
                    ->label('Would Return')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_helpful')
                    ->boolean()
                    ->label('Helpful')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('actual_fit')
                    ->options([
                        'perfect' => 'Perfect',
                        'too_small' => 'Too Small',
                        'too_large' => 'Too Large',
                        'too_tight' => 'Too Tight',
                        'too_loose' => 'Too Loose',
                    ]),

                Tables\Filters\SelectFilter::make('fit_rating')
                    ->options([
                        1 => '1 - Very Poor',
                        2 => '2 - Poor',
                        3 => '3 - Average',
                        4 => '4 - Good',
                        5 => '5 - Excellent',
                    ]),

                Tables\Filters\TernaryFilter::make('would_return')
                    ->label('Would Return')
                    ->placeholder('All')
                    ->trueLabel('Would Return')
                    ->falseLabel('Would Not Return'),

                Tables\Filters\TernaryFilter::make('is_helpful')
                    ->label('Helpful')
                    ->placeholder('All')
                    ->trueLabel('Helpful only')
                    ->falseLabel('Not helpful'),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public')
                    ->placeholder('All')
                    ->trueLabel('Public only')
                    ->falseLabel('Private only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_helpful')
                        ->label('Mark as Helpful')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_helpful' => true]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFitFeedbacks::route('/'),
            'create' => Pages\CreateFitFeedback::route('/create'),
            'edit' => Pages\EditFitFeedback::route('/{record}/edit'),
        ];
    }
}

