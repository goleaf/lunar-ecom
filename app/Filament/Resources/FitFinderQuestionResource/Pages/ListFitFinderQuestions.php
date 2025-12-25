<?php

namespace App\Filament\Resources\FitFinderQuestionResource\Pages;

use App\Filament\Resources\FitFinderQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFitFinderQuestions extends ListRecords
{
    protected static string $resource = FitFinderQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

