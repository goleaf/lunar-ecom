<?php

namespace App\Admin\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Lunar\Panel\Filament\Resources\ProductResource;
use App\Models\Product;

/**
 * Create Product Wizard Page - Step-by-step product creation.
 */
class CreateProductWizard extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected static string $view = 'admin.filament.resources.pages.create-product-wizard';

    public function getFormSchema(): array
    {
        return [
            Wizard::make([
                Step::make('Basic Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Section::make('Product Basics')
                            ->schema([
                                Select::make('product_type_id')
                                    ->label('Product Type')
                                    ->options(\Lunar\Models\ProductType::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->reactive(),
                                
                                TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->unique(\App\Models\Product::class, 'sku')
                                    ->maxLength(255)
                                    ->helperText('Leave empty to auto-generate'),
                                
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        Product::STATUS_DRAFT => 'Draft',
                                        Product::STATUS_ACTIVE => 'Active',
                                        Product::STATUS_PUBLISHED => 'Published (legacy)',
                                        Product::STATUS_ARCHIVED => 'Archived',
                                        Product::STATUS_DISCONTINUED => 'Discontinued',
                                    ])
                                    ->default(Product::STATUS_DRAFT)
                                    ->required(),
                            ]),
                    ]),
                
                Step::make('Details & Description')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('Product Details')
                            ->schema([
                                Textarea::make('short_description')
                                    ->label('Short Description')
                                    ->rows(3)
                                    ->maxLength(500),
                                
                                Textarea::make('full_description')
                                    ->label('Full Description')
                                    ->rows(10)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                
                Step::make('Pricing & Inventory')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Section::make('Pricing')
                            ->schema([
                                TextInput::make('base_price')
                                    ->label('Base Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                            ]),
                    ]),
                
                Step::make('Media & Images')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Section::make('Product Images')
                            ->schema([
                                FileUpload::make('images')
                                    ->label('Product Images')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->directory('products')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->imageEditor()
                                    ->helperText('Drag and drop images or click to upload'),
                            ]),
                    ]),
                
                Step::make('Categories & Collections')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Section::make('Organization')
                            ->schema([
                                Select::make('categories')
                                    ->label('Categories')
                                    ->multiple()
                                    ->options(\App\Models\Category::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload(),
                                
                                Select::make('collections')
                                    ->label('Collections')
                                    ->multiple()
                                    ->options(\Lunar\Models\Collection::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                
                Step::make('SEO & Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Section::make('SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('Meta Title')
                                    ->maxLength(60),
                                
                                Textarea::make('meta_description')
                                    ->label('Meta Description')
                                    ->rows(3)
                                    ->maxLength(160),
                            ]),
                    ]),
            ])
            ->submitAction(view('admin.products.wizard-submit-button'))
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Process wizard data before creating product
        return $data;
    }
}
