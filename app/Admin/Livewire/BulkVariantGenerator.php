<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantCoreService;
use App\Services\VariantMatrixGeneratorService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

/**
 * Bulk Variant Generator - Generate multiple variants at once.
 */
class BulkVariantGenerator extends Component implements HasForms
{
    use InteractsWithForms;

    public Product $product;
    public array $selectedOptions = [];
    public int $defaultStock = 0;
    public ?float $defaultPrice = null;
    public bool $defaultEnabled = true;
    public bool $skipExisting = true;
    public array $previewVariants = [];

    protected VariantCoreService $variantService;
    protected VariantMatrixGeneratorService $matrixGenerator;

    public function boot(VariantCoreService $variantService, VariantMatrixGeneratorService $matrixGenerator)
    {
        $this->variantService = $variantService;
        $this->matrixGenerator = $matrixGenerator;
    }

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    protected function getFormSchema(): array
    {
        $options = $this->product->options()->with('values')->get();

        return [
            Section::make('Variant Generation')
                ->schema([
                    CheckboxList::make('selectedOptions')
                        ->label('Select Option Values')
                        ->options(function () use ($options) {
                            $result = [];
                            foreach ($options as $option) {
                                foreach ($option->values as $value) {
                                    $result[$value->id] = "{$option->name}: {$value->name}";
                                }
                            }
                            return $result;
                        })
                        ->columns(2)
                        ->required()
                        ->descriptions(function () use ($options) {
                            $result = [];
                            foreach ($options as $option) {
                                foreach ($option->values as $value) {
                                    $result[$value->id] = $option->name;
                                }
                            }
                            return $result;
                        })
                        ->searchable(),

                    TextInput::make('defaultStock')
                        ->label('Default Stock')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('defaultPrice')
                        ->label('Default Price')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0),

                    Toggle::make('defaultEnabled')
                        ->label('Enabled by Default')
                        ->default(true),

                    Toggle::make('skipExisting')
                        ->label('Skip Existing Combinations')
                        ->default(true)
                        ->helperText('Skip variants that already exist'),
                ]),
        ];
    }

    public function preview(): void
    {
        $data = $this->form->getState();
        $this->selectedOptions = $data['selectedOptions'] ?? [];
        $this->defaultStock = $data['defaultStock'] ?? 0;
        $this->defaultPrice = $data['defaultPrice'] ?? null;
        $this->defaultEnabled = $data['defaultEnabled'] ?? true;
        $this->skipExisting = $data['skipExisting'] ?? true;

        // Generate preview
        $this->previewVariants = $this->matrixGenerator->generateCombinations(
            $this->product,
            $this->selectedOptions
        );
    }

    public function generate(): void
    {
        $data = $this->form->getState();
        $this->selectedOptions = $data['selectedOptions'] ?? [];

        if (empty($this->selectedOptions)) {
            Notification::make()
                ->title('No Options Selected')
                ->warning()
                ->body('Please select at least one option value.')
                ->send();
            return;
        }

        try {
            DB::transaction(function () {
                $combinations = $this->matrixGenerator->generateCombinations(
                    $this->product,
                    $this->selectedOptions
                );

                $created = 0;
                $skipped = 0;

                foreach ($combinations as $combination) {
                    // Check if variant already exists
                    if ($this->skipExisting) {
                        $exists = ProductVariant::where('product_id', $this->product->id)
                            ->whereHas('variantOptions', function ($q) use ($combination) {
                                $q->whereIn('product_option_values.id', $combination);
                            }, '=', count($combination))
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }
                    }

                    // Create variant
                    $variant = $this->variantService->createVariant(
                        $this->product,
                        [
                            'stock' => $this->defaultStock,
                            'enabled' => $this->defaultEnabled,
                        ],
                        $combination
                    );

                    // Set price
                    if ($this->defaultPrice && $variant) {
                        $currency = \Lunar\Models\Currency::default()->first();
                        if ($currency) {
                            $variant->prices()->create([
                                'currency_id' => $currency->id,
                                'price' => (int)($this->defaultPrice * 100),
                            ]);
                        }
                    }

                    $created++;
                }

                Notification::make()
                    ->title('Variants Generated')
                    ->success()
                    ->body("Created {$created} variants" . ($skipped > 0 ? ", skipped {$skipped} existing" : ''))
                    ->send();

                $this->dispatch('variants-generated');
            });
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function render()
    {
        return view('admin.livewire.bulk-variant-generator');
    }
}

