<?php

namespace App\Filament\Resources\FitFinderQuestionResource\Pages;

use App\Filament\Resources\FitFinderQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFitFinderQuestion extends EditRecord
{
    protected static string $resource = FitFinderQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

