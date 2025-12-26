<?php

namespace App\Http\Resources;

use App\Helpers\CheckoutHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for checkout status.
 */
class CheckoutStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'locked' => $this->resource['locked'] ?? false,
            'can_checkout' => $this->resource['can_checkout'] ?? true,
            'lock_id' => $this->resource['lock_id'] ?? null,
            'state' => $this->resource['state'] ?? null,
            'state_name' => $this->resource['state_name'] ?? 
                (isset($this->resource['state']) ? CheckoutHelper::getStateName($this->resource['state']) : null),
            'phase' => $this->resource['phase'] ?? null,
            'expires_at' => $this->resource['expires_at'] ?? null,
            'can_resume' => $this->resource['can_resume'] ?? false,
            'message' => $this->resource['message'] ?? null,
        ];
    }
}


