<?php

namespace App\Filament\Resources\WorkingDayResource\Pages;

use App\Filament\Resources\WorkingDayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkingDays extends ListRecords
{
    protected static string $resource = WorkingDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
