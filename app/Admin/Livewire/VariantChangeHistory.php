<?php

namespace App\Admin\Livewire;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Variant Change History Timeline - Track changes to variants.
 */
class VariantChangeHistory extends Component
{
    public ProductVariant $variant;
    public array $history = [];

    public function mount(ProductVariant $variant): void
    {
        $this->variant = $variant;
        $this->loadHistory();
    }

    public function loadHistory(): void
    {
        // Get audit trail from activity log or change history table
        // This is a simplified version - you'd integrate with an audit package
        
        $this->history = [
            [
                'type' => 'created',
                'user' => 'System',
                'timestamp' => $this->variant->created_at,
                'changes' => ['Variant created'],
            ],
            [
                'type' => 'updated',
                'user' => 'System',
                'timestamp' => $this->variant->updated_at,
                'changes' => ['Variant updated'],
            ],
        ];

        // If using Laravel Activity Log or similar:
        // $this->history = Activity::where('subject_type', ProductVariant::class)
        //     ->where('subject_id', $this->variant->id)
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($activity) {
        //         return [
        //             'type' => $activity->description,
        //             'user' => $activity->causer->name ?? 'System',
        //             'timestamp' => $activity->created_at,
        //             'changes' => $activity->properties->toArray(),
        //         ];
        //     })
        //     ->toArray();
    }

    public function render()
    {
        return view('admin.livewire.variant-change-history');
    }
}


