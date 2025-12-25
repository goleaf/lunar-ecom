<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantGenerator;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * Livewire component for managing product variants in the admin panel.
 * 
 * Provides:
 * - Variant generation from product options
 * - Bulk editing capabilities
 * - Variant listing and management
 */
class VariantManager extends Component implements HasForms
{
    use InteractsWithForms;

    public Product $product;
    public $variants = [];
    public $selectedVariants = [];
    public $showGenerateForm = false;
    public $showBulkEditForm = false;

    // Generate form data
    public $selectedOptions = [];
    public $defaultStock = 0;
    public $defaultPrice = null;
    public $defaultCurrencyId = null;
    public $defaultEnabled = true;

    // Bulk edit data
    public $bulkStock = null;
    public $bulkPriceOverride = null;
    public $bulkEnabled = null;

    protected VariantGenerator $variantGenerator;

    public function boot(VariantGenerator $variantGenerator)
    {
        $this->variantGenerator = $variantGenerator;
    }

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->loadVariants();
    }

    public function loadVariants()
    {
        $this->variants = $this->product->variants()
            ->with(['variantOptions.option', 'prices.currency'])
            ->get()
            ->toArray();
    }

    public function generateVariants()
    {
        $data = $this->form->getState();

        try {
            $defaults = [
                'stock' => $data['default_stock'] ?? 0,
                'enabled' => $data['default_enabled'] ?? true,
            ];

            if (isset($data['default_price']) && isset($data['default_currency_id'])) {
                $defaults['price'] = (int) ($data['default_price'] * 100);
                $defaults['currency_id'] = $data['default_currency_id'];
            }

            $variants = $this->variantGenerator->generateVariants(
                $this->product,
                $data['selected_options'] ?? [],
                $defaults
            );

            Notification::make()
                ->title('Variants Generated')
                ->success()
                ->body("Successfully generated {$variants->count()} variants.")
                ->send();

            $this->showGenerateForm = false;
            $this->loadVariants();
            $this->reset(['selectedOptions', 'defaultStock', 'defaultPrice', 'defaultCurrencyId', 'defaultEnabled']);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function bulkUpdate()
    {
        $data = $this->form->getState();

        if (empty($this->selectedVariants)) {
            Notification::make()
                ->title('No Variants Selected')
                ->warning()
                ->body('Please select at least one variant to update.')
                ->send();
            return;
        }

        $attributes = [];
        
        if (isset($data['bulk_stock'])) {
            $attributes['stock'] = $data['bulk_stock'];
        }
        
        if (isset($data['bulk_price_override'])) {
            $attributes['price_override'] = (int) ($data['bulk_price_override'] * 100);
        }
        
        if (isset($data['bulk_enabled'])) {
            $attributes['enabled'] = $data['bulk_enabled'];
        }

        if (empty($attributes)) {
            Notification::make()
                ->title('No Changes')
                ->warning()
                ->body('Please specify at least one attribute to update.')
                ->send();
            return;
        }

        try {
            $variants = ProductVariant::whereIn('id', $this->selectedVariants)
                ->where('product_id', $this->product->id)
                ->get();

            $updated = $this->variantGenerator->bulkUpdateVariants($variants, $attributes);

            Notification::make()
                ->title('Variants Updated')
                ->success()
                ->body("Successfully updated {$updated} variants.")
                ->send();

            $this->showBulkEditForm = false;
            $this->selectedVariants = [];
            $this->loadVariants();
            $this->reset(['bulkStock', 'bulkPriceOverride', 'bulkEnabled']);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function deleteVariant($variantId)
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);
            $variant->delete();

            Notification::make()
                ->title('Variant Deleted')
                ->success()
                ->body('Variant has been deleted successfully.')
                ->send();

            $this->loadVariants();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getGenerateFormSchema(): array
    {
        $options = $this->product->productOptions()->with('values')->get();

        return [
            Section::make('Generate Variants')
                ->schema([
                    CheckboxList::make('selected_options')
                        ->label('Product Options')
                        ->options($options->mapWithKeys(function ($option) {
                            return [$option->id => $option->name];
                        }))
                        ->required()
                        ->columns(2),

                    TextInput::make('default_stock')
                        ->label('Default Stock')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    TextInput::make('default_price')
                        ->label('Default Price')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0),

                    Select::make('default_currency_id')
                        ->label('Currency')
                        ->relationship('currency', 'code')
                        ->searchable()
                        ->preload(),

                    Toggle::make('default_enabled')
                        ->label('Enabled by Default')
                        ->default(true),
                ]),
        ];
    }

    protected function getBulkEditFormSchema(): array
    {
        return [
            Section::make('Bulk Edit Variants')
                ->schema([
                    TextInput::make('bulk_stock')
                        ->label('Stock')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Leave empty to keep current value'),

                    TextInput::make('bulk_price_override')
                        ->label('Price Override')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->helperText('Leave empty to keep current value'),

                    Toggle::make('bulk_enabled')
                        ->label('Enabled')
                        ->helperText('Toggle to enable/disable all selected variants'),
                ]),
        ];
    }

    public function render()
    {
        return view('admin.livewire.variant-manager');
    }
}

