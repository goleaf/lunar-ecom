<?php

namespace App\Filament\Resources\FitFinderQuizResource\Pages;

use App\Filament\Resources\FitFinderQuizResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFitFinderQuiz extends EditRecord
{
    protected static string $resource = FitFinderQuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

