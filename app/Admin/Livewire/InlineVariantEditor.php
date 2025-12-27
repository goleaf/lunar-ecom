<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariant;

/**
 * Inline Variant Editor - Edit variants inline in a table.
 */
class InlineVariantEditor extends Component implements HasForms
{
    use InteractsWithForms;

    public Product $product;
    public array $variants = [];
    public ?int $editingVariantId = null;
    public array $editingData = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadVariants();
    }

    public function loadVariants(): void
    {
        $this->variants = $this->product->variants()
            ->with(['prices.currency', 'variantOptions.option'])
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                    'price' => $variant->prices->first()?->price->decimal ?? 0,
                    'enabled' => $variant->enabled ?? true,
                    'options' => $variant->variantOptions->map(function ($vo) {
                        return [
                            'option' => $vo->option->name ?? '',
                            'value' => $vo->optionValue->name ?? '',
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    public function startEditing(int $variantId): void
    {
        $variant = \App\Models\ProductVariant::find($variantId);
        if (!$variant) {
            return;
        }

        $this->editingVariantId = $variantId;
        $this->editingData = [
            'sku' => $variant->sku,
            'stock' => $variant->stock,
            'price' => $variant->prices->first()?->price->decimal ?? 0,
            'enabled' => $variant->enabled ?? true,
        ];
    }

    public function cancelEditing(): void
    {
        $this->editingVariantId = null;
        $this->editingData = [];
    }

    public function saveVariant(): void
    {
        if (!$this->editingVariantId) {
            return;
        }

        $variant = ProductVariant::find($this->editingVariantId);
        if (!$variant) {
            return;
        }

        try {
            DB::transaction(function () use ($variant) {
                // Update variant
                $variant->update([
                    'sku' => $this->editingData['sku'] ?? null,
                    'stock' => $this->editingData['stock'] ?? 0,
                    'enabled' => $this->editingData['enabled'] ?? true,
                ]);

                // Update price
                if (isset($this->editingData['price'])) {
                    $currencyId = \Lunar\Models\Currency::getDefault()->id;
                    $priceInCents = (int)($this->editingData['price'] * 100);

                    $variant->prices()->updateOrCreate(
                        ['currency_id' => $currencyId],
                        ['price' => $priceInCents]
                    );
                }
            });

            $this->loadVariants();
            $this->cancelEditing();

            Notification::make()
                ->title('Variant updated successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating variant')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteVariant(int $variantId): void
    {
        try {
            ProductVariant::find($variantId)?->delete();
            $this->loadVariants();

            Notification::make()
                ->title('Variant deleted successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error deleting variant')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('admin.livewire.inline-variant-editor');
    }
}

