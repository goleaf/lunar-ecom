<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantCoreService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

/**
 * Variant Matrix Editor - Visual grid for managing variants.
 * 
 * Provides a matrix/grid view where:
 * - Rows = one attribute (e.g., Size)
 * - Columns = another attribute (e.g., Color)
 * - Cells = variants with price/stock editing
 */
class VariantMatrixEditor extends Component implements HasForms
{
    use InteractsWithForms;

    public Product $product;
    public array $matrix = [];
    public array $rowAttribute = [];
    public array $columnAttribute = [];
    public array $cellData = [];
    public bool $isEditing = false;

    protected VariantCoreService $variantService;

    public function boot(VariantCoreService $variantService)
    {
        $this->variantService = $variantService;
    }

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadMatrix();
    }

    public function loadMatrix(): void
    {
        // Get product options
        $options = $this->product->options()->with('values')->get();
        
        if ($options->count() < 2) {
            Notification::make()
                ->title('Insufficient Options')
                ->warning()
                ->body('Product needs at least 2 options to create a matrix.')
                ->send();
            return;
        }

        // Use first two options for matrix
        $this->rowAttribute = [
            'id' => $options[0]->id,
            'name' => $options[0]->name,
            'values' => $options[0]->values->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
            ])->toArray(),
        ];

        $this->columnAttribute = [
            'id' => $options[1]->id,
            'name' => $options[1]->name,
            'values' => $options[1]->values->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
            ])->toArray(),
        ];

        // Load existing variants into matrix
        $variants = $this->product->variants()
            ->with(['variantOptions', 'prices.currency'])
            ->get();

        foreach ($this->rowAttribute['values'] as $rowValue) {
            foreach ($this->columnAttribute['values'] as $colValue) {
                $variant = $variants->first(function ($v) use ($rowValue, $colValue) {
                    $optionIds = $v->variantOptions->pluck('id')->toArray();
                    return in_array($rowValue['id'], $optionIds) && 
                           in_array($colValue['id'], $optionIds);
                });

                $this->cellData["{$rowValue['id']}_{$colValue['id']}"] = [
                    'variant_id' => $variant?->id,
                    'sku' => $variant?->sku ?? '',
                    'stock' => $variant?->stock ?? 0,
                    'price' => $variant?->prices->first()?->price->decimal ?? 0,
                    'enabled' => $variant?->enabled ?? true,
                    'exists' => $variant !== null,
                ];
            }
        }
    }

    public function updateCell(string $key, string $field, $value): void
    {
        if (!isset($this->cellData[$key])) {
            return;
        }

        $this->cellData[$key][$field] = $value;
    }

    public function saveCell(string $key): void
    {
        $cell = $this->cellData[$key] ?? null;
        if (!$cell) {
            return;
        }

        [$rowValueId, $colValueId] = explode('_', $key);

        try {
            DB::transaction(function () use ($cell, $rowValueId, $colValueId) {
                if ($cell['exists']) {
                    // Update existing variant
                    $variant = ProductVariant::find($cell['variant_id']);
                    if ($variant) {
                        $variant->update([
                            'stock' => $cell['stock'],
                            'enabled' => $cell['enabled'],
                        ]);

                        // Update price
                        if ($cell['price'] > 0) {
                            $price = $variant->prices()->first();
                            if ($price) {
                                $price->update([
                                    'price' => (int)($cell['price'] * 100),
                                ]);
                            }
                        }
                    }
                } else {
                    // Create new variant
                    $variant = $this->variantService->createVariant(
                        $this->product,
                        [
                            'stock' => $cell['stock'],
                            'enabled' => $cell['enabled'],
                        ],
                        [$rowValueId, $colValueId]
                    );

                    // Set price
                    if ($cell['price'] > 0 && $variant) {
                        $currency = \Lunar\Models\Currency::default()->first();
                        if ($currency) {
                            $variant->prices()->create([
                                'currency_id' => $currency->id,
                                'price' => (int)($cell['price'] * 100),
                            ]);
                        }
                    }

                    $this->cellData[$key]['variant_id'] = $variant->id;
                    $this->cellData[$key]['exists'] = true;
                }
            });

            Notification::make()
                ->title('Variant Saved')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function deleteCell(string $key): void
    {
        $cell = $this->cellData[$key] ?? null;
        if (!$cell || !$cell['exists']) {
            return;
        }

        try {
            ProductVariant::find($cell['variant_id'])?->delete();
            
            $this->cellData[$key] = [
                'variant_id' => null,
                'sku' => '',
                'stock' => 0,
                'price' => 0,
                'enabled' => true,
                'exists' => false,
            ];

            Notification::make()
                ->title('Variant Deleted')
                ->success()
                ->send();
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
        return view('admin.livewire.variant-matrix-editor');
    }
}

