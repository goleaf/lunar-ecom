<?php

namespace App\Filament\Resources\ReferralProgramResource\Pages;

use App\Filament\Resources\ReferralProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferralProgram extends ViewRecord
{
    protected static string $resource = ReferralProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

