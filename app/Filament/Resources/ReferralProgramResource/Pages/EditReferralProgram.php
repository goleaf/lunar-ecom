<?php

namespace App\Filament\Resources\ReferralProgramResource\Pages;

use App\Filament\Resources\ReferralProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferralProgram extends EditRecord
{
    protected static string $resource = ReferralProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analytics')
                ->label('Analytics')
                ->icon('heroicon-o-chart-bar')
                ->url(fn () => static::getUrl('analytics', ['record' => $this->record])),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

