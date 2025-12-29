<?php

namespace App\Filament\Resources\AvailabilityBookingResource\Pages;

use App\Filament\Resources\AvailabilityBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilityBookings extends ListRecords
{
    protected static string $resource = AvailabilityBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

