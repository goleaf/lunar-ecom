<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

/**
 * Inline Variant Price & Stock Editor - Edit price and stock directly in table.
 */
class InlineVariantPriceStockEditor extends Component
{
    public Product $product;
    public array $variants = [];
    public array $editing = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadVariants();
    }

    public function loadVariants(): void
    {
        $this->variants = $this->product->variants()
            ->with(['variantOptions.option', 'prices.currency'])
            ->orderBy('position')
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->getDisplayName(),
                    'stock' => $variant->stock ?? 0,
                    'price' => $variant->prices->first()?->price->decimal ?? 0,
                    'currency' => $variant->prices->first()?->currency->code ?? 'USD',
                    'enabled' => $variant->enabled ?? true,
                    'options' => $variant->variantOptions->map(fn($vo) => 
                        ($vo->option->name ?? '') . ': ' . ($vo->translateAttribute('name') ?? '')
                    )->join(', '),
                ];
            })
            ->toArray();
    }

    public function startEditing(int $variantId, string $field): void
    {
        $this->editing["{$variantId}_{$field}"] = $this->variants[array_search($variantId, array_column($this->variants, 'id'))][$field] ?? '';
    }

    public function stopEditing(int $variantId, string $field): void
    {
        unset($this->editing["{$variantId}_{$field}"]);
    }

    public function updateField(int $variantId, string $field, $value): void
    {
        $key = array_search($variantId, array_column($this->variants, 'id'));
        if ($key === false) {
            return;
        }

        $this->variants[$key][$field] = $value;
    }

    public function saveField(int $variantId, string $field): void
    {
        $key = array_search($variantId, array_column($this->variants, 'id'));
        if ($key === false) {
            return;
        }

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            return;
        }

        try {
            DB::transaction(function () use ($variant, $field, $key) {
                $value = $this->variants[$key][$field];

                if ($field === 'stock') {
                    $variant->update(['stock' => (int)$value]);
                } elseif ($field === 'price') {
                    $price = $variant->prices()->first();
                    if ($price) {
                        $price->update(['price' => (int)($value * 100)]);
                    } else {
                        $currency = \Lunar\Models\Currency::default()->first();
                        if ($currency) {
                            $variant->prices()->create([
                                'currency_id' => $currency->id,
                                'price' => (int)($value * 100),
                            ]);
                        }
                    }
                } elseif ($field === 'enabled') {
                    $variant->update(['enabled' => (bool)$value]);
                }
            });

            $this->stopEditing($variantId, $field);

            Notification::make()
                ->title('Updated')
                ->success()
                ->body("Variant {$field} updated successfully.")
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
        return view('admin.livewire.inline-variant-price-stock-editor');
    }
}


