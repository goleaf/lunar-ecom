<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

/**
 * Drag & Drop Variant Ordering - Reorder variants via drag and drop.
 */
class DragDropVariantOrder extends Component
{
    public Product $product;
    public array $variants = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadVariants();
    }

    public function loadVariants(): void
    {
        $this->variants = $this->product->variants()
            ->with(['variantOptions.option'])
            ->orderBy('position')
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->getDisplayName(),
                    'position' => $variant->position ?? 0,
                    'options' => $variant->variantOptions->map(fn($vo) => 
                        ($vo->option->name ?? '') . ': ' . ($vo->translateAttribute('name') ?? '')
                    )->join(', '),
                ];
            })
            ->toArray();
    }

    public function updateOrder(array $order): void
    {
        try {
            DB::transaction(function () use ($order) {
                foreach ($order as $index => $variantId) {
                    ProductVariant::where('id', $variantId)
                        ->where('product_id', $this->product->id)
                        ->update(['position' => $index + 1]);
                }
            });

            $this->loadVariants();

            Notification::make()
                ->title('Order Updated')
                ->success()
                ->body('Variant order has been updated.')
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
        return view('admin.livewire.drag-drop-variant-order');
    }
}


