<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductQuestionResource\Pages;
use App\Filament\Resources\ProductQuestionResource\RelationManagers\AnswersRelationManager;
use App\Models\ProductQuestion;
use App\Services\QuestionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductQuestionResource extends Resource
{
    protected static ?string $model = ProductQuestion::class;

    protected static ?string $slug = 'ops-product-questions';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Product Q&A';

    protected static ?int $navigationSort = 36;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product',
                'customer',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->label('Product')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (ProductQuestion $record): string => (string) (
                                $record->product?->translate('name') ?? "Product #{$record->product_id}"
                            ))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('customer_name')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('email')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\Textarea::make('question')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('question_original')
                            ->label('Original question')
                            ->disabled()
                            ->columnSpanFull()
                            ->hidden(fn (?ProductQuestion $record): bool => blank($record?->question_original) || $record?->question_original === $record?->question),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Moderation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'spam' => 'Spam',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('is_public')
                            ->default(true),

                        Forms\Components\Toggle::make('is_answered')
                            ->disabled(),

                        Forms\Components\TextInput::make('asked_at')
                            ->disabled(),

                        Forms\Components\TextInput::make('moderated_at')
                            ->disabled()
                            ->hidden(fn (?ProductQuestion $record): bool => blank($record?->moderated_at)),

                        Forms\Components\Textarea::make('moderation_notes')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Engagement')
                    ->schema([
                        Forms\Components\TextInput::make('views_count')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('helpful_count')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('not_helpful_count')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (ProductQuestion $record): string => (string) (
                        $record->product?->translate('name') ?? "Product #{$record->product_id}"
                    ))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'spam' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_answered')
                    ->label('Answered')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('asked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'spam' => 'Spam',
                    ]),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public'),

                Tables\Filters\TernaryFilter::make('is_answered')
                    ->label('Answered'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProductQuestion $record): bool => $record->status !== 'approved')
                    ->requiresConfirmation()
                    ->action(function (ProductQuestion $record): void {
                        $record->forceFill(['is_public' => true])->save();
                        app(QuestionService::class)->moderateQuestion($record, 'approved');
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ProductQuestion $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(function (ProductQuestion $record): void {
                        $record->forceFill(['is_public' => false])->save();
                        app(QuestionService::class)->moderateQuestion($record, 'rejected');
                    }),

                Tables\Actions\Action::make('spam')
                    ->label('Spam')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (ProductQuestion $record): bool => $record->status !== 'spam')
                    ->requiresConfirmation()
                    ->action(function (ProductQuestion $record): void {
                        $record->forceFill(['is_public' => false])->save();
                        app(QuestionService::class)->moderateQuestion($record, 'spam');
                    }),

                Tables\Actions\Action::make('answer')
                    ->label('Answer')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('answer')
                            ->required()
                            ->minLength(10)
                            ->maxLength(2000)
                            ->rows(4),

                        Forms\Components\Toggle::make('is_official')
                            ->default(true),
                    ])
                    ->action(function (ProductQuestion $record, array $data): void {
                        app(QuestionService::class)->submitAnswer($record, [
                            'answer' => $data['answer'],
                            'answerer_type' => 'admin',
                            'answerer_id' => null,
                            'is_official' => $data['is_official'] ?? true,
                            'auto_approve' => true,
                        ]);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('asked_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductQuestions::route('/'),
            'view' => Pages\ViewProductQuestion::route('/{record}'),
            'edit' => Pages\EditProductQuestion::route('/{record}/edit'),
        ];
    }
}

