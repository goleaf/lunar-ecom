<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use App\Services\ReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'customer']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review')
                    ->schema([
                        Forms\Components\Placeholder::make('product')
                            ->content(fn (Review $record): string => (string) ($record->product?->translate('name') ?? "Product #{$record->product_id}")),

                        Forms\Components\Placeholder::make('customer')
                            ->content(fn (Review $record): string => (string) (
                                data_get($record->customer, 'fullName')
                                    ?? data_get($record->customer, 'full_name')
                                    ?? $record->name
                                    ?? $record->email
                                    ?? 'Guest'
                            )),

                        Forms\Components\Select::make('rating')
                            ->options([
                                1 => '1',
                                2 => '2',
                                3 => '3',
                                4 => '4',
                                5 => '5',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('pros')
                            ->label('Pros')
                            ->reorderable()
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('cons')
                            ->label('Cons')
                            ->reorderable()
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('recommended')
                            ->default(true),

                        Forms\Components\Toggle::make('is_verified_purchase')
                            ->label('Verified purchase')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Moderation')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approved')
                            ->helperText('Prefer using the Approve / Unapprove actions on the list, but this is editable.')
                            ->default(false),

                        Forms\Components\Toggle::make('is_reported')
                            ->label('Reported')
                            ->default(false),

                        Forms\Components\TextInput::make('report_count')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\Textarea::make('admin_response')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (Review $record): string => (string) ($record->product?->translate('name') ?? "Product #{$record->product_id}"))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(fn (Review $record): string => (string) (
                        data_get($record->customer, 'fullName')
                            ?? data_get($record->customer, 'full_name')
                            ?? $record->name
                            ?? $record->email
                            ?? 'Guest'
                    ))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_reported')
                    ->label('Reported')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('report_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved'),

                Tables\Filters\TernaryFilter::make('is_reported')
                    ->label('Reported'),

                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        1 => '1',
                        2 => '2',
                        3 => '3',
                        4 => '4',
                        5 => '5',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => ! $record->is_approved)
                    ->action(function (Review $record): void {
                        app(ReviewService::class)->approveReview($record, null);
                    }),

                Tables\Actions\Action::make('unapprove')
                    ->label('Unapprove')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => (bool) $record->is_approved)
                    ->action(function (Review $record): void {
                        app(ReviewService::class)->rejectReview($record, null);
                    }),

                Tables\Actions\Action::make('respond')
                    ->label('Respond')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('response')
                            ->required()
                            ->minLength(10)
                            ->maxLength(2000),
                    ])
                    ->action(function (Review $record, array $data): void {
                        app(ReviewService::class)->addAdminResponse($record, $data['response'], null);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approve selected')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            app(ReviewService::class)->approveReview($record, null);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('bulk_unapprove')
                    ->label('Unapprove selected')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            app(ReviewService::class)->rejectReview($record, null);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}

