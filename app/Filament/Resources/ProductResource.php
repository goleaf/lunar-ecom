<?php

namespace App\Filament\Resources;

use App\Admin\Support\Forms\Components\Attributes;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\BadgeAssignmentsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\CustomizationExamplesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\CustomizationsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\UrlsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\VersionsRelationManager;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Models\Brand;
use Lunar\Models\ProductType;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Core')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('product_type_id')
                            ->label('Product Type')
                            ->options(fn () => ProductType::query()->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options([
                                Product::STATUS_DRAFT => 'Draft',
                                Product::STATUS_ACTIVE => 'Active',
                                Product::STATUS_PUBLISHED => 'Published (legacy)',
                                Product::STATUS_ARCHIVED => 'Archived',
                                Product::STATUS_DISCONTINUED => 'Discontinued',
                            ])
                            ->required(),
                        Select::make('visibility')
                            ->options([
                                Product::VISIBILITY_PUBLIC => 'Public',
                                Product::VISIBILITY_PRIVATE => 'Private',
                                Product::VISIBILITY_SCHEDULED => 'Scheduled',
                            ])
                            ->required(),
                        Select::make('brand_id')
                            ->label('Brand')
                            ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('manufacturer_name')
                            ->label('Manufacturer')
                            ->maxLength(255),
                        Toggle::make('is_bundle')
                            ->label('Bundle Product'),
                        Toggle::make('is_digital')
                            ->label('Digital Product'),
                    ])
                    ->columns(2),
                Section::make('Descriptions')
                    ->schema([
                        Textarea::make('short_description')
                            ->rows(3)
                            ->maxLength(500),
                        RichEditor::make('full_description')
                            ->label('Full Description'),
                        RichEditor::make('technical_description')
                            ->label('Technical Description'),
                    ]),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('meta_title')
                            ->maxLength(255),
                        Textarea::make('meta_description')
                            ->rows(3),
                        Textarea::make('meta_keywords')
                            ->rows(2),
                    ])
                    ->columns(2),
                Section::make('Publishing & Locking')
                    ->schema([
                        DateTimePicker::make('published_at')
                            ->label('Published At'),
                        DateTimePicker::make('scheduled_publish_at')
                            ->label('Scheduled Publish At')
                            ->visible(fn (Forms\Get $get) => $get('visibility') === Product::VISIBILITY_SCHEDULED),
                        DateTimePicker::make('scheduled_unpublish_at')
                            ->label('Scheduled Unpublish At'),
                        Toggle::make('is_locked')
                            ->label('Locked')
                            ->disabled(fn (?Product $record) => $record?->exists),
                        Textarea::make('lock_reason')
                            ->rows(2)
                            ->visible(fn (?Product $record) => $record?->is_locked ?? false),
                        TextInput::make('version')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('Attributes')
                    ->schema([
                        Attributes::make()
                            ->label('Attribute Data'),
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
                    ->label('Name')
                    ->getStateUsing(fn (Product $record) => $record->translateAttribute('name') ?? 'Unnamed')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('productType.name')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Product::STATUS_DRAFT => 'Draft',
                        Product::STATUS_ACTIVE => 'Active',
                        Product::STATUS_PUBLISHED => 'Published (legacy)',
                        Product::STATUS_ARCHIVED => 'Archived',
                        Product::STATUS_DISCONTINUED => 'Discontinued',
                    ]),
                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        Product::VISIBILITY_PUBLIC => 'Public',
                        Product::VISIBILITY_PRIVATE => 'Private',
                        Product::VISIBILITY_SCHEDULED => 'Scheduled',
                    ]),
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BadgeAssignmentsRelationManager::class,
            CustomizationsRelationManager::class,
            CustomizationExamplesRelationManager::class,
            UrlsRelationManager::class,
            VersionsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            // Avoid collisions with legacy `/admin/products/*` routes (e.g. import/export tools).
            'view' => Pages\ViewProduct::route('/{record}/view'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
