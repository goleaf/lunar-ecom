<?php

namespace App\Admin\Livewire;

use App\Models\Review;
use App\Services\ReviewService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Collection;

/**
 * Livewire component for review moderation queue in admin panel.
 */
class ReviewModerationQueue extends Component implements HasForms
{
    use InteractsWithForms;

    public Collection $reviews;
    public string $status = 'pending';
    public array $selectedReviews = [];
    public ?Review $selectedReview = null;
    public bool $showResponseModal = false;
    public string $adminResponse = '';

    protected ReviewService $reviewService;

    public function boot(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function mount(): void
    {
        $this->loadReviews();
    }

    public function loadReviews(): void
    {
        $query = Review::with(['product', 'customer', 'order', 'media'])
            ->orderByDesc('created_at');

        switch ($this->status) {
            case 'pending':
                $query->pending();
                break;
            case 'approved':
                $query->approved();
                break;
            case 'reported':
                $query->reported();
                break;
        }

        $this->reviews = $query->get();
    }

    public function updatedStatus(): void
    {
        $this->selectedReviews = [];
        $this->loadReviews();
    }

    public function approveReview(Review $review): void
    {
        try {
            $this->reviewService->approveReview($review, auth()->id());
            Notification::make()
                ->title('Review approved successfully')
                ->success()
                ->send();
            $this->loadReviews();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function rejectReview(Review $review): void
    {
        try {
            $this->reviewService->rejectReview($review);
            Notification::make()
                ->title('Review rejected successfully')
                ->success()
                ->send();
            $this->loadReviews();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function bulkApprove(): void
    {
        if (empty($this->selectedReviews)) {
            Notification::make()
                ->title('Please select reviews to approve')
                ->warning()
                ->send();
            return;
        }

        try {
            $count = $this->reviewService->bulkApprove($this->selectedReviews);
            Notification::make()
                ->title("{$count} review(s) approved successfully")
                ->success()
                ->send();
            $this->selectedReviews = [];
            $this->loadReviews();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function bulkReject(): void
    {
        if (empty($this->selectedReviews)) {
            Notification::make()
                ->title('Please select reviews to reject')
                ->warning()
                ->send();
            return;
        }

        try {
            $count = $this->reviewService->bulkReject($this->selectedReviews);
            Notification::make()
                ->title("{$count} review(s) rejected successfully")
                ->success()
                ->send();
            $this->selectedReviews = [];
            $this->loadReviews();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openResponseModal(Review $review): void
    {
        $this->selectedReview = $review;
        $this->adminResponse = $review->admin_response ?? '';
        $this->showResponseModal = true;
    }

    public function saveAdminResponse(): void
    {
        $this->validate([
            'adminResponse' => 'required|string|min:10|max:2000',
        ]);

        try {
            $this->reviewService->addAdminResponse(
                $this->selectedReview,
                $this->adminResponse,
                auth()->id()
            );
            Notification::make()
                ->title('Admin response added successfully')
                ->success()
                ->send();
            $this->showResponseModal = false;
            $this->selectedReview = null;
            $this->adminResponse = '';
            $this->loadReviews();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('admin.livewire.review-moderation-queue');
    }
}


