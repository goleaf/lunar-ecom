<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use App\Models\ProductWorkflowHistory;
use Livewire\Component;

/**
 * Change History Timeline - Display product change history.
 */
class ChangeHistoryTimeline extends Component
{
    public Product $product;
    public array $history = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadHistory();
    }

    public function loadHistory(): void
    {
        // Get workflow history
        $workflowHistory = ProductWorkflowHistory::where('product_id', $this->product->id)
            ->orderByDesc('created_at')
            ->with('user')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'workflow',
                    'action' => $item->action,
                    'from_status' => $item->from_status,
                    'to_status' => $item->to_status,
                    'user' => $item->user?->name ?? 'System',
                    'timestamp' => $item->created_at,
                    'notes' => $item->notes,
                ];
            });

        // Get activity log (if using Spatie Activity Log)
        $activityLog = [];
        if (method_exists($this->product, 'activities')) {
            $activityLog = $this->product->activities()
                ->orderByDesc('created_at')
                ->with('causer')
                ->get()
                ->map(function ($activity) {
                    return [
                        'type' => 'activity',
                        'action' => $activity->description,
                        'changes' => $activity->properties->get('attributes', []),
                        'user' => $activity->causer?->name ?? 'System',
                        'timestamp' => $activity->created_at,
                        'notes' => null,
                    ];
                });
        }

        // Merge and sort by timestamp
        $this->history = $workflowHistory->merge($activityLog)
            ->sortByDesc('timestamp')
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('admin.livewire.change-history-timeline');
    }
}

