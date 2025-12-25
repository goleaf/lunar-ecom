<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

/**
 * Bulk Attribute Editor - Edit attributes for multiple products at once.
 */
class BulkAttributeEditor extends Component implements HasForms
{
    use InteractsWithForms;

    public array $selectedProducts = [];
    public array $attributes = [];
    public array $values = [];

    public function mount(array $productIds = []): void
    {
        $this->selectedProducts = $productIds;
        $this->loadAttributes();
    }

    public function loadAttributes(): void
    {
        // Get available attributes
        $this->attributes = \Lunar\Models\Attribute::where('attribute_type', \Lunar\Models\Product::class)
            ->get()
            ->mapWithKeys(function ($attr) {
                return [$attr->id => $attr->name];
            })
            ->toArray();
    }

    public function save(): void
    {
        if (empty($this->selectedProducts)) {
            Notification::make()
                ->title('No products selected')
                ->warning()
                ->send();
            return;
        }

        try {
            DB::transaction(function () {
                $products = Product::whereIn('id', $this->selectedProducts)->get();
                
                foreach ($products as $product) {
                    foreach ($this->values as $attributeId => $value) {
                        if ($value === null || $value === '') {
                            continue;
                        }

                        $attribute = \Lunar\Models\Attribute::find($attributeId);
                        if (!$attribute) {
                            continue;
                        }

                        // Set attribute value
                        $product->setAttributeValue($attribute->handle, $value);
                    }
                }
            });

            Notification::make()
                ->title('Attributes updated successfully')
                ->body('Updated ' . count($this->selectedProducts) . ' products')
                ->success()
                ->send();

            $this->dispatch('bulk-edit-completed');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating attributes')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('admin.livewire.bulk-attribute-editor');
    }
}

