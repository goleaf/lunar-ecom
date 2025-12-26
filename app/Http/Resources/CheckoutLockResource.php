<?php

namespace App\Http\Resources;

use App\Helpers\CheckoutHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for checkout lock.
 */
class CheckoutLockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'state' => $this->state,
            'state_name' => CheckoutHelper::getStateName($this->state),
            'phase' => $this->phase,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_failed' => $this->isFailed(),
            'is_expired' => $this->isExpired(),
            'can_resume' => $this->canResume(),
            'duration' => $this->completed_at || $this->failed_at
                ? CheckoutHelper::formatDuration($this->resource)
                : null,
            'failure_reason' => $this->failure_reason,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}


