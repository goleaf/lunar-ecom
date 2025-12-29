<?php

namespace App\Filament\Resources\PriceMatrixResource\Pages;

use App\Filament\Resources\PriceMatrixResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceMatrix extends CreateRecord
{
    protected static string $resource = PriceMatrixResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $requiresApproval = (bool) ($data['requires_approval'] ?? false);

        $data['approval_status'] = $requiresApproval ? 'pending' : 'approved';

        if (! $requiresApproval) {
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        }

        return $data;
    }
}

