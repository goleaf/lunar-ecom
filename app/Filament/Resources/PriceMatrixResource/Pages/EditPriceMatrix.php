<?php

namespace App\Filament\Resources\PriceMatrixResource\Pages;

use App\Filament\Resources\PriceMatrixResource;
use Filament\Resources\Pages\EditRecord;

class EditPriceMatrix extends EditRecord
{
    protected static string $resource = PriceMatrixResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $requiresApproval = (bool) ($data['requires_approval'] ?? false);

        if (! $requiresApproval) {
            $data['approval_status'] = 'approved';
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        } elseif (empty($data['approval_status'])) {
            $data['approval_status'] = 'pending';
        }

        return $data;
    }
}

