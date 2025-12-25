<?php

namespace App\Filament\Resources\FitFeedbackResource\Pages;

use App\Filament\Resources\FitFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFitFeedback extends EditRecord
{
    protected static string $resource = FitFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

