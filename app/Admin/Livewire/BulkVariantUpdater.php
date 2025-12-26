<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
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
 * Bulk Variant Updater - Update multiple variants at once.
 */
class BulkVariantUpdater extends Component implements HasForms
{
    use InteractsWithForms;

    public Product $product;
    public array $selectedVariants = [];
    public ?int $bulkStock = null;
    public ?float $bulkPrice = null;
    public ?bool $bulkEnabled = null;
    public ?string $bulkStatus = null;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Bulk Update Variants')
                ->schema([
                    TextInput::make('bulkStock')
                        ->label('Stock')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Leave empty to keep current value'),

                    TextInput::make('bulkPrice')
                        ->label('Price')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->helperText('Leave empty to keep current value'),

                    Toggle::make('bulkEnabled')
                        ->label('Enabled')
                        ->helperText('Toggle to enable/disable all selected variants'),

                    Select::make('bulkStatus')
                        ->label('Status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'archived' => 'Archived',
                        ])
                        ->helperText('Leave empty to keep current value'),
                ]),
        ];
    }

    public function update(): void
    {
        if (empty($this->selectedVariants)) {
            Notification::make()
                ->title('No Variants Selected')
                ->warning()
                ->body('Please select at least one variant.')
                ->send();
            return;
        }

        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($data) {
                $variants = ProductVariant::whereIn('id', $this->selectedVariants)
                    ->where('product_id', $this->product->id)
                    ->get();

                $updated = 0;

                foreach ($variants as $variant) {
                    $updates = [];

                    if (isset($data['bulkStock']) && $data['bulkStock'] !== null) {
                        $updates['stock'] = (int)$data['bulkStock'];
                    }

                    if (isset($data['bulkEnabled']) && $data['bulkEnabled'] !== null) {
                        $updates['enabled'] = (bool)$data['bulkEnabled'];
                    }

                    if (isset($data['bulkStatus']) && $data['bulkStatus']) {
                        $updates['status'] = $data['bulkStatus'];
                    }

                    if (!empty($updates)) {
                        $variant->update($updates);
                        $updated++;
                    }

                    // Update price separately
                    if (isset($data['bulkPrice']) && $data['bulkPrice'] !== null) {
                        $price = $variant->prices()->first();
                        if ($price) {
                            $price->update(['price' => (int)($data['bulkPrice'] * 100)]);
                        } else {
                            $currency = \Lunar\Models\Currency::default()->first();
                            if ($currency) {
                                $variant->prices()->create([
                                    'currency_id' => $currency->id,
                                    'price' => (int)($data['bulkPrice'] * 100),
                                ]);
                            }
                        }
                    }
                }

                Notification::make()
                    ->title('Variants Updated')
                    ->success()
                    ->body("Successfully updated {$updated} variants.")
                    ->send();

                $this->selectedVariants = [];
                $this->reset(['bulkStock', 'bulkPrice', 'bulkEnabled', 'bulkStatus']);
                $this->dispatch('variants-updated');
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
        return view('admin.livewire.bulk-variant-updater');
    }
}


