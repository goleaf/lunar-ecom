<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Product Wizard - Step-by-step product creation.
 * 
 * Provides a multi-step wizard for creating products:
 * 1. Basic Information
 * 2. Details & Description
 * 3. Pricing & Inventory
 * 4. Media & Images
 * 5. Categories & Collections
 * 6. SEO & Settings
 */
class ProductWizard extends Component implements HasForms
{
    use InteractsWithForms;

    public ?Product $product = null;
    public array $data = [];
    public int $currentStep = 0;

    public function mount(?Product $product = null): void
    {
        $this->product = $product;
        
        if ($product) {
            $this->form->fill($product->toArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (!$this->product) {
                                                $set('sku', Str::slug($state));
                                            }
                                        }),
                                    
                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->unique(Product::class, 'sku', ignoreRecord: $this->product)
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
                                        ->maxLength(500)
                                        ->helperText('Brief product summary'),
                                    
                                    Textarea::make('full_description')
                                        ->label('Full Description')
                                        ->rows(10)
                                        ->columnSpanFull(),
                                    
                                    Textarea::make('technical_description')
                                        ->label('Technical Details')
                                        ->rows(5)
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
                                        ->required()
                                        ->helperText('Default price for variants'),
                                    
                                    Select::make('currency_id')
                                        ->label('Currency')
                                        ->options(\Lunar\Models\Currency::pluck('code', 'id'))
                                        ->default(fn() => \Lunar\Models\Currency::getDefault()?->id)
                                        ->required(),
                                ]),
                            
                            Section::make('Inventory')
                                ->schema([
                                    TextInput::make('default_stock')
                                        ->label('Default Stock')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Default stock level for variants'),
                                    
                                    Toggle::make('track_inventory')
                                        ->label('Track Inventory')
                                        ->default(true),
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
                                        ->imageEditorAspectRatios([
                                            '16:9',
                                            '4:3',
                                            '1:1',
                                        ])
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
                                    
                                    Select::make('brand_id')
                                        ->label('Brand')
                                        ->options(\Lunar\Models\Brand::pluck('name', 'id'))
                                        ->searchable(),
                                ]),
                        ]),
                    
                    Step::make('SEO & Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('SEO')
                                ->schema([
                                    TextInput::make('meta_title')
                                        ->label('Meta Title')
                                        ->maxLength(60)
                                        ->helperText('Recommended: 50-60 characters'),
                                    
                                    Textarea::make('meta_description')
                                        ->label('Meta Description')
                                        ->rows(3)
                                        ->maxLength(160)
                                        ->helperText('Recommended: 150-160 characters'),
                                    
                                    TextInput::make('meta_keywords')
                                        ->label('Meta Keywords')
                                        ->helperText('Comma-separated keywords'),
                                ]),
                            
                            Section::make('Additional Settings')
                                ->schema([
                                    Toggle::make('is_featured')
                                        ->label('Featured Product')
                                        ->default(false),
                                    
                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ]),
                        ]),
                ])
                ->submitAction(fn() => view('admin.products.wizard-submit-button'))
                ->cancelAction(fn() => view('admin.products.wizard-cancel-button'))
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            DB::beginTransaction();
            
            // Create product
            $product = Product::create([
                'product_type_id' => $data['product_type_id'],
                'status' => $data['status'] ?? 'draft',
                'sku' => $data['sku'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'full_description' => $data['full_description'] ?? null,
                'technical_description' => $data['technical_description'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ]);
            
            // Set name attribute
            $nameAttribute = \Lunar\Models\Attribute::where('handle', 'name')->first();
            if ($nameAttribute) {
                $attributeData = $product->attribute_data ?? [];
                $attributeData[$nameAttribute->handle] = [
                    \Lunar\Facades\Language::getDefault()->code => $data['name'],
                ];
                $product->attribute_data = $attributeData;
                $product->save();
            }
            
            // Attach categories
            if (isset($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }
            
            // Attach collections
            if (isset($data['collections'])) {
                $product->collections()->sync($data['collections']);
            }
            
            // Set brand
            if (isset($data['brand_id'])) {
                $product->brand_id = $data['brand_id'];
                $product->save();
            }
            
            // Create default variant
            $variant = $product->variants()->create([
                'sku' => $data['sku'] ?? null,
                'stock' => $data['default_stock'] ?? 0,
            ]);
            
            // Set price
            if (isset($data['base_price'])) {
                $currencyId = $data['currency_id'] ?? \Lunar\Models\Currency::getDefault()->id;
                $priceInCents = (int)($data['base_price'] * 100);
                
                $variant->prices()->create([
                    'currency_id' => $currencyId,
                    'price' => $priceInCents,
                ]);
            }
            
            // Handle images
            if (isset($data['images'])) {
                foreach ($data['images'] as $image) {
                    $product->addMediaFromDisk($image, 'public')
                        ->toMediaCollection('images');
                }
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Product created successfully')
                ->success()
                ->send();
            
            return redirect()->route('filament.admin.resources.products.edit', $product);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error creating product')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('admin.livewire.product-wizard');
    }
}
