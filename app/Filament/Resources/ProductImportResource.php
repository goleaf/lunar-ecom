<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductImportResource\Pages\CreateProductImport;
use App\Filament\Resources\ProductImportResource\Pages\ListProductImports;
use App\Filament\Resources\ProductImportResource\Pages\ViewProductImport;
use App\Filament\Resources\ProductImportResource\RelationManagers\RollbacksRelationManager;
use App\Filament\Resources\ProductImportResource\RelationManagers\RowsRelationManager;
use App\Models\ProductImport;
use App\Services\ProductImportService;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductImportResource extends Resource
{
    protected static ?string $model = ProductImport::class;

    protected static ?string $slug = 'ops-product-imports';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Product Imports';

    protected static ?int $navigationSort = 80;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Import file')
                ->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->label('File')
                        ->disk('local')
                        ->directory('imports')
                        ->storeFileNamesIn('original_filename')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(10240)
                        ->required()
                        ->getUploadedFileNameForStorageUsing(function ($file): string {
                            return time().'_'.$file->getClientOriginalName();
                        }),

                    Forms\Components\Select::make('options.action')
                        ->label('Action')
                        ->options([
                            'create' => 'Create new products only',
                            'update' => 'Update existing products only',
                            'create_or_update' => 'Create or update',
                        ])
                        ->default('create_or_update')
                        ->required(),

                    Forms\Components\Toggle::make('options.skip_errors')
                        ->label('Skip errors')
                        ->default(true)
                        ->helperText('If enabled, the import continues after a failed row.'),

                    Forms\Components\Textarea::make('field_mapping')
                        ->label('Field mapping (JSON, optional)')
                        ->rows(4)
                        ->helperText('Example: {"sku":"SKU","name":"Name","price":"Price"}')
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                        ->dehydrateStateUsing(function ($state) {
                            if (is_array($state)) {
                                return $state;
                            }
                            if (! is_string($state) || trim($state) === '') {
                                return [];
                            }
                            $decoded = json_decode($state, true);
                            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                        })
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('status')->default('pending'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('File')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->getStateUsing(fn (ProductImport $record): string => $record->total_rows > 0
                        ? "{$record->processed_rows} / {$record->total_rows} ({$record->getProgressPercentage()}%)"
                        : "{$record->processed_rows} / {$record->total_rows}")
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('results')
                    ->label('Results')
                    ->getStateUsing(fn (ProductImport $record): string => "{$record->successful_rows} success / {$record->failed_rows} failed")
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('rollback')
                    ->label('Rollback')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ProductImport $record): bool => $record->canRollback())
                    ->action(function (ProductImport $record): void {
                        $result = app(ProductImportService::class)->rollback($record, auth('web')->id());

                        if (! ($result['success'] ?? false)) {
                            Notification::make()
                                ->title('Rollback failed')
                                ->danger()
                                ->send();
                            return;
                        }

                        $rolledBack = (int) ($result['rolled_back'] ?? 0);
                        Notification::make()
                            ->title("Rollback complete ({$rolledBack} items)")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RowsRelationManager::class,
            RollbacksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductImports::route('/'),
            'create' => CreateProductImport::route('/create'),
            'view' => ViewProductImport::route('/{record}'),
        ];
    }
}

